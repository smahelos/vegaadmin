<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Domain\Invoice\DTO\InvoiceProductDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Address;
use App\Models\Business;
use App\Models\Contact;
use App\Models\EntityLimitUsage;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class InvoiceCrudApplicationServiceTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function a_failure_while_storing_the_lines_leaves_the_invoice_and_its_lines_untouched(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'invoice_vs' => 'BEFORE',
            'payment_currency' => 'CZK',
        ]);
        $line = InvoiceProduct::factory()->create(['invoice_id' => $invoice->id]);
        $this->app->bind(InvoiceProductDtoWriteRepositoryInterface::class, fn () => $this->failingLineRepository());

        try {
            $this->app->make(InvoiceCrudApplicationServiceInterface::class)->update(
                new UserId($user->id),
                InvoiceId::fromInt($invoice->id),
                [
                    'invoice_vs' => 'AFTER',
                    'payment_currency' => 'CZK',
                    'payment_status_id' => $invoice->payment_status_id,
                ],
                new InvoicePartySelection(
                    BusinessId::fromInt($invoice->business_id),
                    null,
                    null,
                    new DocumentPartyDTO(
                        'Odběratel s.r.o.',
                        null,
                        null,
                        null,
                        null,
                        null,
                    ),
                    false,
                    false,
                ),
                [new InvoiceProductWriteData(
                    'New line',
                    '1',
                    Unit::PIECES,
                    Money::fromString('10', 'CZK'),
                    '21',
                )],
            );
            $this->fail('The update did not fail');
        } catch (RuntimeException) {
        }

        $this->assertSame('BEFORE', $invoice->fresh()->invoice_vs);
        $this->assertSame([$line->id], InvoiceProduct::where('invoice_id', $invoice->id)->pluck('id')->all());
    }

    #[Test]
    public function a_later_line_failure_rolls_back_the_new_contact_address_and_invoice(): void
    {
        $user = $this->frontendUser();
        $business = Business::factory()->create(['user_id' => $user->id]);
        Address::factory()->create(['business_id' => $business->id]);
        $beforeAddresses = Address::count();
        $beforeUsage = EntityLimitUsage::where('user_id', $user->id)->count();
        $this->app->bind(InvoiceProductDtoWriteRepositoryInterface::class, fn () => $this->failingLineRepository());
        try {
            $this->app->make(InvoiceCrudApplicationServiceInterface::class)->create(
                $this->idOf($user),
                [
                    'invoice_vs' => '20260042',
                    'issue_date' => '2026-01-01',
                    'tax_point_date' => '2026-01-01',
                    'due_in' => 14,
                    'payment_currency' => 'CZK',
                    'payment_method_id' => PaymentMethod::where('slug', 'cash')->value('id'),
                    'payment_status_id' => Status::where('slug', 'pending')->value('id'),
                ],
                new InvoicePartySelection(
                    BusinessId::fromInt($business->id),
                    null,
                    null,
                    new DocumentPartyDTO(
                        'Atomic customer',
                        null,
                        null,
                        $this->brnoAddress(),
                        null,
                        null,
                    ),
                    true,
                    false,
                ),
                [new InvoiceProductWriteData(
                    'Line',
                    '1',
                    Unit::PIECES,
                    Money::fromString('10', 'CZK'),
                    '21',
                )],
            );
            $this->fail('The line write did not fail');
        } catch (RuntimeException $exception) {
            $this->assertSame('Storing the lines failed', $exception->getMessage());
        }
        $this->assertSame(0, Contact::where('user_id', $user->id)->count());
        $this->assertSame(0, Invoice::where('user_id', $user->id)->count());
        $this->assertSame($beforeAddresses, Address::count());
        $this->assertSame($beforeUsage, EntityLimitUsage::where('user_id', $user->id)->count());
    }

    /**
     * Deletes the old lines for real, then fails before the new ones are stored.
     */
    private function failingLineRepository(): InvoiceProductDtoWriteRepositoryInterface
    {
        return new class implements InvoiceProductDtoWriteRepositoryInterface
        {
            public function create(InvoiceId $invoiceId, InvoiceProductWriteData $data): InvoiceProductDTO
            {
                throw new RuntimeException('Storing a line failed');
            }

            public function bulkCreate(InvoiceId $invoiceId, array $products): void
            {
                throw new RuntimeException('Storing the lines failed');
            }

            public function deleteByInvoiceId(InvoiceId $invoiceId): void
            {
                InvoiceProduct::where('invoice_id', $invoiceId->getValue())->delete();
            }

            public function allForInvoice(InvoiceId $invoiceId): array
            {
                return [];
            }

            public function listForInvoiceWithProduct(InvoiceId $invoiceId): array
            {
                return [];
            }
        };
    }
}
