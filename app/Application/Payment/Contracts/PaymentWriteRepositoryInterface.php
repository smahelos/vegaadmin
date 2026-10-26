<?php

namespace App\Application\Payment\Contracts;

use App\Models\Payment;

interface PaymentWriteRepositoryInterface
{
    public function create(array $attributes): Payment;
}
