<?php

namespace Koderia\Upperlink\Tests;

use Illuminate\Support\Facades\Http;
use Koderia\Upperlink\UpperlinkClient;
use Tests\TestCase;

class UpperlinkTest extends TestCase
{
    protected UpperlinkClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new UpperlinkClient([
            'checkout_url'    => 'https://paygate.upperlink.ng/paygate/checkout',
            'requery_url'     => 'https://paygate.upperlink.ng/paygate/requery',
            'username'        => 'test_user',
            'password'        => 'secret_pwd',
            'merchant_id'     => 'MERCHANT_001',
            'split_mode'      => true,
            'split_code'      => 'UPL_SPLIT_123',
            'redirect_url'    => 'https://example.com/callback',
            'convenience_fee' => 10000,
            'logging'         => false,
        ]);
    }

    public function test_calculate_payable_amount_uncapped_fee(): void
    {
        // ₦10,000 = 1,000,000 kobo
        // 1.5% fee = 15,000 kobo
        // ₦50 fee = 5,000 kobo
        // Total = 1,020,000 kobo
        $total = $this->client->calculatePayableAmount(1000000, 5000);
        $this->assertEquals(1020000, $total);
    }

    public function test_calculate_payable_amount_capped_fee(): void
    {
        // ₦200,000 = 20,000,000 kobo
        // 1.5% is 300,000 kobo -> capped at 200,000 kobo
        // ₦50 fee = 5,000 kobo
        // Total = 20,205,000 kobo
        $total = $this->client->calculatePayableAmount(20000000, 5000);
        $this->assertEquals(20205000, $total);
    }

    public function test_initialize_transaction_success(): void
    {
        Http::fake([
            'https://paygate.upperlink.ng/paygate/checkout' => Http::response([
                'code' => '200',
                'message' => 'Success',
                'data' => [
                    'checkOutUrl' => 'https://checkout.upperlink.ng/session/abc123xyz',
                    'payGateRef'  => 'UPL_REF_98765',
                ]
            ], 200)
        ]);

        $response = $this->client->initialize([
            'amount'     => 10200,
            'payGateRef' => 'LOCAL_REF_111',
            'email'      => 'payer@example.com',
            'phone'      => '08012345678',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->failed());
        $this->assertEquals('https://checkout.upperlink.ng/session/abc123xyz', $response->getCheckoutUrl());
        $this->assertEquals('UPL_REF_98765', $response->getPayGateRef());
    }

    public function test_initialize_transaction_failure(): void
    {
        Http::fake([
            'https://paygate.upperlink.ng/paygate/checkout' => Http::response([
                'code' => '401',
                'message' => 'Unauthorized merchant credentials',
                'data' => []
            ], 200)
        ]);

        $response = $this->client->initialize([
            'amount'     => 10200,
            'payGateRef' => 'LOCAL_REF_111',
        ]);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->failed());
        $this->assertEquals('Unauthorized merchant credentials', $response->getErrorMessage());
    }

    public function test_verify_transaction_approved(): void
    {
        Http::fake([
            'https://paygate.upperlink.ng/paygate/requery*' => Http::response([
                'transactionStatus' => '00',
                'amount'            => 10200,
                'ref'               => 'LOCAL_REF_111',
                'paymentDate'       => '2026-11-20T22:00:00Z',
                'meta'              => json_encode(['invoice_id' => 999]),
            ], 200)
        ]);

        $response = $this->client->verify('LOCAL_REF_111');

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isApproved());
        $this->assertEquals(10200, $response->getAmount());
        $this->assertEquals('LOCAL_REF_111', $response->getReference());
        $this->assertEquals(999, $response->getMeta()['invoice_id']);
    }

    public function test_verify_transaction_declined(): void
    {
        Http::fake([
            'https://paygate.upperlink.ng/paygate/requery*' => Http::response([
                'transactionStatus' => '01',
                'message'           => 'Transaction declined by bank',
            ], 200)
        ]);

        $response = $this->client->verify('LOCAL_REF_111');

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isApproved());
        $this->assertTrue($response->failed());
    }
}
