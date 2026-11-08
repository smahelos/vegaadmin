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
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\BuildsInvoiceInput;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class InvoiceCrudApplicationServiceTest extends TestCase
{
    use BuildsInvoiceInput;
    use BuildsParties;

    #[Test]
    public function a_failure_while_storing_the_lines_leaves_the_invoice_and_its_lines_untouched(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'invoice_vs' => '20260001',
                'payment_currency' => 'CZK',
            ],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        $line = $invoiceProductFactory->create(['invoice_id' => $invoice->id]);
        $this->app->bind(InvoiceProductDtoWriteRepositoryInterface::class, fn () => $this->failingLineRepository());

        try {
            $invoiceService = $this->app->make(InvoiceCrudApplicationServiceInterface::class);
            $invoiceService->update(
                new UserId($user->id),
                InvoiceId::fromInt($invoice->id),
                $this->invoiceWriteData('20260002'),
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

        $this->assertSame('20260001', $invoice->fresh()->invoice_vs);
        $invoiceProductQuery = InvoiceProduct::where('invoice_id', $invoice->id);
        $invoiceProductValues = $invoiceProductQuery->pluck('id');
        $this->assertSame(
            [$line->id],
            $invoiceProductValues->all(),
        );
    }

    #[Test]
    public function a_later_line_failure_rolls_back_the_new_contact_address_and_invoice(): void
    {
        $user = $this->frontendUser();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $user->id]);
        $addressFactory = Address::factory();
        $addressFactory->create(['business_id' => $business->id]);
        $beforeAddresses = Address::count();
        $this->app->bind(InvoiceProductDtoWriteRepositoryInterface::class, fn () => $this->failingLineRepository());
        try {
            $invoiceService = $this->app->make(InvoiceCrudApplicationServiceInterface::class);
            $invoiceService->create(
                $this->idOf($user),
                $this->invoiceWriteData('20260042'),
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
        $contactQuery = Contact::where('user_id', $user->id);
        $this->assertSame(
            0,
            $contactQuery->count(),
        );
        $invoiceQuery = Invoice::where('user_id', $user->id);
        $this->assertSame(
            0,
            $invoiceQuery->count(),
        );
        $this->assertSame($beforeAddresses, Address::count());
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
                $invoiceProductQuery = InvoiceProduct::where('invoice_id', $invoiceId->getValue());
                $invoiceProductQuery->delete();
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
