<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
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
            Money::fromString('9007199254740993.01', 'CZK'),
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
            Money::fromString('121', 'EUR'),
        );

        $this->assertSame('20260042', $attributes['invoice_vs']);
        $this->assertNull($attributes['invoice_ks']);
        $this->assertSame('1234', $attributes['invoice_ss']);
        $this->assertSame('2026-03-01', $attributes['issue_date']);
        $this->assertSame('2026-02-28', $attributes['tax_point_date']);
        $this->assertSame(14, $attributes['due_in']);
        $this->assertSame(3, $attributes['payment_method_id']);
        $this->assertSame('EUR', $attributes['payment_currency']);
        $this->assertNull($attributes['invoice_text']);
        $this->assertSame('minimal', $attributes['template']);
        $this->assertSame(7, $attributes['business_id']);
        $this->assertSame('Odběratel s.r.o.', $attributes['customer_name']);
        $this->assertArrayNotHasKey('user_id', $attributes);
    }

    private function writeData(): InvoiceWriteData
    {
        return new InvoiceWriteData(
            number: new InvoiceNumber('20260042'),
            constantSymbol: null,
            specificSymbol: '1234',
            issueDate: new DateTimeImmutable('2026-03-01'),
            taxPointDate: new DateTimeImmutable('2026-02-28'),
            dueIn: 14,
            currency: Currency::EUR,
            status: InvoiceStatus::PENDING,
            paymentMethodId: PaymentMethodId::fromInt(3),
            text: null,
            template: InvoiceTemplate::MINIMAL,
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
