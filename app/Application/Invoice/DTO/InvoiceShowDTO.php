<?php

namespace App\Application\Invoice\DTO;

use App\Application\Shared\Money\DTO\MoneyDTO;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use Illuminate\Database\Eloquent\Model;

/**
 * Structured DTO for invoice show view.
 * Keeps Eloquent invoice model for Blade accessors, and attaches related DTOs and computed money values.
 */
class InvoiceShowDTO
{
    public function __construct(
        public readonly Model $invoice,
        public readonly ?ClientDTO $client,
        public readonly ?SupplierDTO $supplier,
        public readonly MoneyDTO $paymentAmount,
        public readonly MoneyDTO $subtotal,
        public readonly MoneyDTO $totalTax,
        /** @var array{limit:int,current_usage:int,allowed:bool} */
        public readonly array $limitsData,
    ) {}

    /**
     * Return array representation suitable for views and APIs.
     * Preserves Eloquent model instance to keep dynamic accessors and relations.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'invoice' => $this->invoice, // preserve model
            'client' => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'email' => $this->client->email,
                'phone' => $this->client->phone,
                'street' => $this->client->street,
                'city' => $this->client->city,
                'zip' => $this->client->zip,
                'country' => $this->client->country,
                'ico' => $this->client->ico,
                'dic' => $this->client->dic,
            ] : null,
            'supplier' => $this->supplier ? [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'email' => $this->supplier->email,
                'phone' => $this->supplier->phone,
                'street' => $this->supplier->street,
                'city' => $this->supplier->city,
                'zip' => $this->supplier->zip,
                'country' => $this->supplier->country,
                'ico' => $this->supplier->ico,
                'dic' => $this->supplier->dic,
                'supplier_logo' => $this->supplier->supplier_logo,
                'account_number' => $this->supplier->account_number,
                'bank_code' => $this->supplier->bank_code,
                'bank_name' => $this->supplier->bank_name,
                'iban' => $this->supplier->iban,
                'swift' => $this->supplier->swift,
            ] : null,
            'paymentAmount' => $this->paymentAmount->toArray(),
            'subtotal' => $this->subtotal->toArray(),
            'totalTax' => $this->totalTax->toArray(),
            'limitsData' => $this->limitsData,
        ];
    }
}
