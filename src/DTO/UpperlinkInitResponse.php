<?php

namespace Koderia\Upperlink\DTO;

class UpperlinkInitResponse
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $checkoutUrl = null,
        public readonly ?string $payGateRef = null,
        public readonly ?string $code = null,
        public readonly array $rawResponse = [],
        public readonly ?string $errorMessage = null
    ) {}

    public static function fromResponse(array $response): self
    {
        $code = (string) ($response['code'] ?? '');
        $data = $response['data'] ?? [];
        $isSuccess = ($code === '200' || $code === '00') && !empty($data['checkOutUrl']);

        return new self(
            successful: $isSuccess,
            checkoutUrl: $data['checkOutUrl'] ?? null,
            payGateRef: $data['payGateRef'] ?? null,
            code: $code,
            rawResponse: $response,
            errorMessage: $isSuccess ? null : ($response['message'] ?? 'Transaction initiation failed.')
        );
    }

    public static function failure(string $errorMessage, array $rawResponse = []): self
    {
        return new self(
            successful: false,
            checkoutUrl: null,
            payGateRef: null,
            code: null,
            rawResponse: $rawResponse,
            errorMessage: $errorMessage
        );
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function failed(): bool
    {
        return !$this->successful;
    }

    public function getCheckoutUrl(): ?string
    {
        return $this->checkoutUrl;
    }

    public function getPayGateRef(): ?string
    {
        return $this->payGateRef;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function toArray(): array
    {
        return [
            'successful'    => $this->successful,
            'checkout_url'  => $this->checkoutUrl,
            'pay_gate_ref'  => $this->payGateRef,
            'code'          => $this->code,
            'error_message' => $this->errorMessage,
            'raw_response'  => $this->rawResponse,
        ];
    }
}
