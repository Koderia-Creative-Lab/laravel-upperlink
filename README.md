# Laravel Upperlink (Paygate / PayChoice)

A Laravel package for integrating Upperlink Paygate payment services, supporting transaction initialization, checkout redirection, re-query verification, split settlements, and fee computation.

## Installation

```bash
composer require koderia-creative-lab/laravel-upperlink
```

Publish configuration:

```bash
php artisan vendor:publish --tag="upperlink-config"
```

## Configuration

Set the following environment variables in `.env`:

```env
UPPERLINK_CHECKOUT_URL=https://paygate.upperlink.ng/paygate/checkout
UPPERLINK_REQUERY_URL=https://paygate.upperlink.ng/paygate/requery
UPPERLINK_CHECKOUT_USERNAME=your_username
UPPERLINK_CHECKOUT_PASSWORD=your_password
UPPERLINK_REF=your_merchant_id
UPPERLINK_REDIRECT_URL=https://your-app.com/payment/callback
UPPERLINK_SPLIT_MODE=false
UPPERLINK_SPLIT_CODE=your_split_code
UPPERLINK_CONVENIENCE_FEE=10000
UPPERLINK_TIMEOUT=30
UPPERLINK_LOGGING=true
```

## Usage

### Initializing a Payment

```php
use Koderia\Upperlink\Facades\Upperlink;

$response = Upperlink::initialize([
    'amount'     => 15000, // Amount in Naira
    'payGateRef' => 'UNIQUE_TX_REFERENCE',
    'email'      => 'customer@example.com',
    'phone'      => '08012345678',
    'firstName'  => 'Ada',
    'lastName'   => 'Lovelace',
    'meta'       => ['order_id' => 102],
]);

if ($response->isSuccessful()) {
    // Redirect customer to Upperlink checkout
    return redirect($response->getCheckoutUrl());
}

$error = $response->getErrorMessage();
```

### Verifying a Transaction

```php
use Koderia\Upperlink\Facades\Upperlink;

$verification = Upperlink::verify($reference);

if ($verification->isApproved()) {
    $amount = $verification->getAmount();
    $meta   = $verification->getMeta();
}
```

### Calculating Payable Total (1.5% Cap + Convenience Fee)

```php
use Koderia\Upperlink\Facades\Upperlink;

// Input base amount in Kobo (e.g., ₦10,000 = 1,000,000 kobo)
$totalKobo = Upperlink::calculatePayableAmount(1000000);
```

## Testing

```bash
vendor/bin/phpunit
```
