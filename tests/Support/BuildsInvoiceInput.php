<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;

/**
 * The input of the invoice form: a signed-in user selects a business and its account and enters the customer,
 * a guest enters both parties and the account.
 */
trait BuildsInvoiceInput
{
    /**
     * A business of the user with the address an invoice prints, and its account.
     *
     * @return array{Business, BankAccount}
     */
    private function issuerOf(User $user): array
    {
        $business = Business::factory()->create(['user_id' => $user->id]);
        Address::factory()->create(['business_id' => $business->id]);

        return [$business, BankAccount::factory()->ownedBy($business)->create()];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invoiceInput(
        Business $business,
        ?BankAccount $account,
        array $overrides = [],
    ): array {
        return $overrides + [
            ...$this->invoiceDetailsInput(),
            'business_id' => $business->id,
            'bank_account_id' => $account?->id,
            ...$this->partyInput('customer', 'Odběratel s.r.o.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function guestInvoiceInput(array $overrides = []): array
    {
        return $overrides + [
            ...$this->invoiceDetailsInput(),
            ...$this->partyInput('supplier', 'Dodavatel s.r.o.'),
            ...$this->partyInput('customer', 'Odběratel s.r.o.'),
            'account_number' => '2001234561',
            'account_bank_code' => '2010',
            'account_country' => 'CZ',
        ];
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function invoiceItem(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Consulting',
            'quantity' => '1',
            'unit' => 'hours',
            'price' => '100',
            'currency' => 'CZK',
            'tax_rate' => '0',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceDetailsInput(): array
    {
        return [
            'invoice_vs' => 'INV-' . uniqid(),
            'payment_method_id' => PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail()->id,
            'payment_status_id' => Status::where('slug', InvoiceStatus::PENDING->value)->firstOrFail()->id,
            'issue_date' => now()->toDateString(),
            'tax_point_date' => now()->toDateString(),
            'due_in' => 14,
            'payment_currency' => 'CZK',
            'invoice-products' => json_encode([$this->invoiceItem()]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function partyInput(string $role, string $name): array
    {
        return [
            $role . '_name' => $name,
            $role . '_street' => 'Hlavní 1',
            $role . '_city' => 'Brno',
            $role . '_postal_code' => '602 00',
            $role . '_country' => 'CZ',
        ];
    }
}
