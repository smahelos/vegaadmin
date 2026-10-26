<?php

namespace App\Application\Product\Contracts;

use App\Models\InvoiceProduct;

interface InvoiceProductWriteRepositoryInterface
{
    public function create(array $data): InvoiceProduct;
}
