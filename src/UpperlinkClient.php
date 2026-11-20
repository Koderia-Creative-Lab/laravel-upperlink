<?php

namespace Koderia\Upperlink;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Koderia\Upperlink\DTO\UpperlinkInitResponse;
use Koderia\Upperlink\DTO\UpperlinkVerifyResponse;
use Throwable;

class UpperlinkClient
{
    protected string $checkoutUrl;
    protected string $requeryUrl;
    protected ?string $username;
    protected ?string $password;
    protected ?string $merchantId;
    protected bool $splitMode;
    protected ?string $splitCode;
    protected ?string $redirectUrl;
    protected int $convenienceFee;
    protected int $timeout;
    protected bool $logging;

    public function __construct(array $config = [])
    {
        $this->checkoutUrl = (string) ($config['checkout_url'] ?? 'https://paygate.upperlink.ng/paygate/checkout');
        $this->requeryUrl = (string) ($config['requery_url'] ?? 'https://paygate.upperlink.ng/paygate/requery');
        $this->username = $config['username'] ?? null;
        $this->password = $config['password'] ?? null;
        $this->merchantId = $config['merchant_id'] ?? null;
        $this->splitMode = (bool) ($config['split_mode'] ?? false);
        $this->splitCode = $config['split_code'] ?? null;
        $this->redirectUrl = $config['redirect_url'] ?? null;
        $this->convenienceFee = (int) ($config['convenience_fee'] ?? 10000);
        $this->timeout = (int) ($config['timeout'] ?? 30);
        $this->logging = (bool) ($config['logging'] ?? true);
    }

    /**
     * Initialize an Upperlink Paygate transaction and retrieve the checkout URL.
     *
     * @param array $payload Transaction parameters (amount, email, phone, payGateRef, etc.)
     * @return UpperlinkInitResponse
     */
    public function initialize(array $payload): UpperlinkInitResponse
    {
        $data = array_merge([
            'merchantId'  => $this->merchantId,
            'redirectUrl' => $this->redirectUrl,
            'countryCode' => 'NG',
            'currency'    => 'NGN',
        ], $payload);

        // Inject split code if enabled and not already provided
        if ($this->splitMode && !empty($this->splitCode) && !isset($data['splitCode'])) {
            $data['splitCode'] = $this->splitCode;
        }

        // Ensure meta is a JSON string if array was provided
        if (isset($data['meta']) && is_array($data['meta'])) {
            $data['meta'] = json_encode($data['meta']);
        }

        $this->logInfo('Upperlink initialize request:', ['payload' => $data]);

        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth((string) $this->username, (string) $this->password)
                ->asJson()
                ->post($this->checkoutUrl, $data);

            $responseData = $response->json();

            if (!is_array($responseData)) {
                $responseData = json_decode($response->body(), true) ?: [];
            }

            $this->logInfo('Upperlink initialize response:', ['response' => $responseData]);

            return UpperlinkInitResponse::fromResponse($responseData);
        } catch (Throwable $e) {
            $this->logError("Upperlink initialize exception: {$e->getMessage()}");
            return UpperlinkInitResponse::failure($e->getMessage());
        }
    }

    /**
     * Verify / Requery an Upperlink transaction by merchant reference.
     *
     * @param string $reference
     * @return UpperlinkVerifyResponse
     */
    public function verify(string $reference): UpperlinkVerifyResponse
    {
        $url = rtrim($this->requeryUrl, '/') . '?' . http_build_query([
            'merchantId' => $this->merchantId,
            'ref'        => $reference,
        ]);

        $this->logInfo("Upperlink verify request for ref: {$reference}");

        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth((string) $this->username, (string) $this->password)
                ->get($url);

            $responseData = $response->json();

            if (!is_array($responseData)) {
                $responseData = json_decode($response->body(), true) ?: [];
            }

            $this->logInfo("Upperlink verify response for ref [{$reference}]:", ['response' => $responseData]);

            return UpperlinkVerifyResponse::fromResponse($responseData);
        } catch (Throwable $e) {
            $this->logError("Upperlink verify exception for [{$reference}]: {$e->getMessage()}");
            return UpperlinkVerifyResponse::failure($e->getMessage());
        }
    }

    /**
     * Calculate payable total in Kobo based on 1.5% fee capped at ₦2,000 (200,000 kobo) + convenience fee.
     *
     * @param float|int $amountInKobo Base principal amount in Kobo
     * @param int|null $customConvenienceFee Custom convenience fee in Kobo
     * @return int Total amount in Kobo
     */
    public function calculatePayableAmount(float|int $amountInKobo, ?int $customConvenienceFee = null): int
    {
        $convenienceFee = $customConvenienceFee ?? $this->convenienceFee;
        $baseFee = (1.5 / 100) * $amountInKobo;
        $maxCap = 200000; // 200,000 kobo = ₦2,000 max card processing fee cap

        if ($baseFee > $maxCap) {
            $baseFee = $maxCap;
        }

        return (int) round($amountInKobo + $baseFee + $convenienceFee);
    }

    protected function logInfo(string $message, array $context = []): void
    {
        if ($this->logging) {
            Log::info($message, $context);
        }
    }

    protected function logError(string $message, array $context = []): void
    {
        if ($this->logging) {
            Log::error($message, $context);
        }
    }
}
