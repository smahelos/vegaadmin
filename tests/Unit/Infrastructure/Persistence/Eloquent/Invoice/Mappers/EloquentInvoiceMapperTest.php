<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\DTO\InvoiceIssue;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Translation\Enums\Language;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EloquentInvoiceMapperTest extends TestCase
{
    #[Test]
    public function the_total_is_stored_as_the_exact_decimal_string_of_its_money(): void
    {
        $mapper = new EloquentInvoiceMapper;

        // Through a float this amount would be stored as 9007199254740992.00
        $attributes = $mapper->toAttributes(
            $this->writeData(),
            $this->parties(),
            Money::fromString('9007199254740993.01', Currency::CZK),
        );

        $this->assertSame('9007199254740993.01', $attributes['payment_amount']);
    }

    #[Test]
    public function every_entered_field_is_written_so_an_update_replaces_the_invoice(): void
    {
        $mapper = new EloquentInvoiceMapper;

        $attributes = $mapper->toAttributes(
            $this->writeData(),
            $this->parties(),
            Money::fromString('121', Currency::EUR),
        );

        $this->assertNull($attributes['invoice_ks']);
        $this->assertSame('1234', $attributes['invoice_ss']);
        $this->assertSame(14, $attributes['due_in']);
        $this->assertSame(3, $attributes['payment_method_id']);
        $this->assertSame('EUR', $attributes['payment_currency']);
        $this->assertNull($attributes['invoice_text']);
        $this->assertSame('minimal', $attributes['template']);
        $this->assertSame('de', $attributes['document_language']);
        $this->assertSame(7, $attributes['business_id']);
        $this->assertSame('Odběratel s.r.o.', $attributes['customer_name']);
        $this->assertArrayNotHasKey('user_id', $attributes);
        // The number and the dates are given at issue, never by an update of the draft
        $this->assertArrayNotHasKey('invoice_vs', $attributes);
        $this->assertArrayNotHasKey('issue_date', $attributes);
    }

    #[Test]
    public function issuing_stores_the_number_the_dates_and_the_copied_settings(): void
    {
        $mapper = new EloquentInvoiceMapper;

        $attributes = $mapper->toIssueAttributes(
            new InvoiceIssue(
                number: new InvoiceNumber('20260042'),
                date: new DateTimeImmutable('2026-03-01'),
                taxPointDate: new DateTimeImmutable('2026-02-28'),
                issuedAt: new DateTimeImmutable('2026-03-01 10:15:00'),
                issuedBy: UserId::fromInt(5),
                vatPayer: true,
                registerEntry: 'C 1234 vedená u KS v Brně',
            ),
        );

        $this->assertSame('20260042', $attributes['invoice_vs']);
        $this->assertSame('2026-03-01', $attributes['issue_date']);
        $this->assertSame('2026-02-28', $attributes['tax_point_date']);
        $this->assertSame(5, $attributes['issued_by']);
        $this->assertTrue($attributes['is_vat_payer']);
        $this->assertSame('C 1234 vedená u KS v Brně', $attributes['register_entry']);
    }

    private function writeData(): InvoiceWriteData
    {
        return new InvoiceWriteData(
            constantSymbol: null,
            specificSymbol: '1234',
            dueIn: 14,
            currency: Currency::EUR,
            paymentMethodId: PaymentMethodId::fromInt(3),
            text: null,
            template: InvoiceTemplate::MINIMAL,
            language: Language::DE,
        );
    }

    private function parties(): DocumentParties
    {
        return new DocumentParties(
            businessId: BusinessId::fromInt(7),
            contactId: null,
            bankAccountId: null,
            supplier: new DocumentPartyDTO(
                'Dodavatel s.r.o.',
                null,
                null,
                null,
                null,
                null,
            ),
            customer: new DocumentPartyDTO(
                'Odběratel s.r.o.',
                null,
                null,
                null,
                null,
                null,
            ),
            account: null,
        );
    }
}
