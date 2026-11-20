<?php

namespace Koderia\Upperlink\DTO;

class UpperlinkVerifyResponse
{
    public function __construct(
        public readonly bool $successful,
        public readonly bool $approved,
        public readonly ?string $transactionStatus = null,
        public readonly float|int $amount = 0,
        public readonly ?string $reference = null,
        public readonly ?string $paymentDate = null,
        public readonly array $meta = [],
        public readonly array $rawResponse = [],
        public readonly ?string $errorMessage = null
    ) {}

    public static function fromResponse(array $response): self
    {
        $status = (string) ($response['transactionStatus'] ?? '');
        $isApproved = ($status === '00');
        
        $metaData = [];
        if (!empty($response['meta'])) {
            $metaData = is_array($response['meta']) ? $response['meta'] : (json_decode($response['meta'], true) ?: []);
        }

        return new self(
            successful: true,
            approved: $isApproved,
            transactionStatus: $status,
            amount: (float) ($response['amount'] ?? 0),
            reference: $response['ref'] ?? ($response['payGateRef'] ?? null),
            paymentDate: $response['paymentDate'] ?? null,
            meta: $metaData,
            rawResponse: $response,
            errorMessage: $isApproved ? null : ($response['message'] ?? "Transaction status: {$status}")
        );
    }

    public static function failure(string $errorMessage, array $rawResponse = []): self
    {
        return new self(
            successful: false,
            approved: false,
            transactionStatus: null,
            amount: 0,
            reference: null,
            paymentDate: null,
            meta: [],
            rawResponse: $rawResponse,
            errorMessage: $errorMessage
        );
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function failed(): bool
    {
        return !$this->approved;
    }

    public function getAmount(): float|int
    {
        return $this->amount;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function toArray(): array
    {
        return [
            'successful'         => $this->successful,
            'approved'           => $this->approved,
            'transaction_status' => $this->transactionStatus,
            'amount'             => $this->amount,
            'reference'          => $this->reference,
            'payment_date'       => $this->paymentDate,
            'meta'               => $this->meta,
            'error_message'      => $this->errorMessage,
            'raw_response'       => $this->rawResponse,
        ];
    }
}
