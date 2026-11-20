<?php

namespace Koderia\Upperlink\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Koderia\Upperlink\DTO\UpperlinkInitResponse initialize(array $payload)
 * @method static \Koderia\Upperlink\DTO\UpperlinkVerifyResponse verify(string $reference)
 * @method static int calculatePayableAmount(float|int $amountInKobo, ?int $customConvenienceFee = null)
 *
 * @see \Koderia\Upperlink\UpperlinkClient
 */
class Upperlink extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'upperlink';
    }
}
