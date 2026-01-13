<?php

namespace App\Domain\Payment\Adapters;

use App\Domain\Payment\ValueObjects\PaymentAmount;

/**
 * Adapter to transform PaymentAmount value object into array / primitive structures.
 */
class PaymentAmountAdapter
{
    /**
     * Convert to array representation.
     *
     * @return array{amount:float,currency:string,formatted:string,cents:int}
     */
    public function toArray(PaymentAmount $amount): array
    {
        return [
            'amount' => $amount->getAmount(),
            'currency' => $amount->getCurrency(),
            'formatted' => $amount->getFormattedAmount(),
            'cents' => $amount->getAmountInCents(),
        ];
    }
}
