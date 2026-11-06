<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\PaymentMethod;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

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
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $user->id]);
        $addressFactory = Address::factory();
        $addressFactory->create(['business_id' => $business->id]);

        $bankAccountFactory = BankAccount::factory();
        $ownedFactory = $bankAccountFactory->ownedBy($business);

        return [$business, $ownedFactory->create()];
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
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value);
        $now = CarbonImmutable::now();

        return [
            'invoice_vs' => $this->invoiceNumber(),
            'payment_method_id' => $paymentMethodQuery->firstOrFail()->id,
            'status' => InvoiceStatus::PENDING->value,
            'template' => InvoiceTemplate::DEFAULT->value,
            'issue_date' => $now->toDateString(),
            'tax_point_date' => $now->toDateString(),
            'due_in' => 14,
            'payment_currency' => 'CZK',
            'invoice-products' => json_encode([$this->invoiceItem()]),
        ];
    }

    /** What the form would send for an invoice in CZK paid by bank transfer, as the requests build it. */
    private function invoiceWriteData(?string $number = null): InvoiceWriteData
    {
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value);
        $paymentMethod = $paymentMethodQuery->firstOrFail();
        $today = new DateTimeImmutable('today');

        return new InvoiceWriteData(
            number: new InvoiceNumber($number ?? $this->invoiceNumber()),
            constantSymbol: null,
            specificSymbol: null,
            issueDate: $today,
            taxPointDate: $today,
            dueIn: 14,
            currency: Currency::CZK,
            status: InvoiceStatus::PENDING,
            paymentMethodId: PaymentMethodId::fromInt($paymentMethod->id),
            text: null,
            template: InvoiceTemplate::DEFAULT,
        );
    }

    /** A number of this year in the InvoiceNumber format, unlikely to repeat within a test. */
    private function invoiceNumber(): string
    {
        $now = CarbonImmutable::now();
        $number = InvoiceNumber::fromYearAndSequence(
            $now->year,
            random_int(1, 999999),
        );

        return $number->getNumber();
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
