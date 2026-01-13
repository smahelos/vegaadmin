<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Application\Product\Contracts\InvoiceProductReadRepositoryInterface;
use App\Application\Product\Contracts\InvoiceProductWriteRepositoryInterface;
use App\Models\InvoiceProduct;

class EloquentInvoiceProductRepository implements InvoiceProductReadRepositoryInterface, InvoiceProductWriteRepositoryInterface
{
    public function create(array $data): InvoiceProduct
    {
        return InvoiceProduct::create($data);
    }

    public function allForInvoice(int $invoiceId): array
    {
        return InvoiceProduct::where('invoice_id',$invoiceId)
            ->with(relations: ['product'])
            ->get()
            ->toArray();
    }

    public function bulkCreate(int $invoiceId, array $products): void
    {
        foreach ($products as $product) {
            $isCustom = !isset($product['product_id']) || empty($product['product_id']) ||
                (isset($product['is_custom_product']) && $product['is_custom_product']);

            $quantity = (float)($product['quantity'] ?? 1);
            $price = (float)($product['price'] ?? 0);
            $taxRate = (float)($product['tax_rate'] ?? 21);

            $taxAmount = ($quantity * $price * $taxRate) / 100;
            $totalPrice = $quantity * $price * (1 + $taxRate / 100);

            InvoiceProduct::create([
                'invoice_id' => $invoiceId,
                'product_id' => $isCustom ? null : ($product['product_id'] ?? null),
                'name' => $product['name'] ?? '',
                'quantity' => $quantity,
                'price' => $price,
                'currency' => $product['currency'] ?? 'CZK',
                'unit' => $product['unit'] ?? 'ks',
                'category' => $product['category'] ?? null,
                'description' => $product['description'] ?? null,
                'is_custom_product' => $isCustom,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_price' => $totalPrice,
            ]);
        }
    }

    public function deleteByInvoiceId(int $invoiceId): void
    {
        InvoiceProduct::where('invoice_id', $invoiceId)->delete();
    }

    public function listForInvoiceWithProduct(int $invoiceId): array
    {
        return InvoiceProduct::where('invoice_id', $invoiceId)
            ->with(['product'])
            ->get()
            ->toArray();
    }
}
