<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice;

use App\Application\Invoice\Commands\CopyInvoice;
use App\Application\Invoice\Commands\DeleteInvoice;
use App\Application\Invoice\Commands\IssueInvoice;
use App\Application\Invoice\Commands\LinkInvoiceContact;
use App\Application\Invoice\Commands\SetInvoiceStatus;
use App\Application\Invoice\Commands\SetInvoiceTemplate;
use App\Application\Invoice\Queries\GetInvoicePdf;
use App\Application\Invoice\Services\GuestInvoiceCreateForm;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\Exceptions\InvoiceIssuedException;
use App\Domain\Invoice\Exceptions\InvoiceNotIssuedException;
use App\Domain\Invoice\Exceptions\InvoiceNumberTakenException;
use App\Domain\Invoice\Exceptions\InvoiceNumberYearException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\Support\Fakes\Shared\FixedClock;
use Tests\Support\RunsUseCases;
use Tests\TestCase;

/**
 * The life cycle of an invoice (D-050): issuing a draft, the lock of an issued invoice, deleting and copying.
 */
final class InvoiceIssuingTest extends TestCase
{
    use BuildsParties;
    use RunsUseCases;

    private const ISSUE_DATE = '2026-03-10';

    private User $user;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->frontendUser();
        $businessFactory = Business::factory();
        $this->business = $businessFactory->create(
            [
                'user_id' => $this->user->id,
                'is_vat_payer' => true,
                'register_entry' => 'C 12345 vedená u Krajského soudu v Brně',
            ],
        );
    }

    #[Test]
    public function issuing_numbers_the_draft_and_copies_the_suppliers_settings_and_vat_lines(): void
    {
        $draft = $this->draft();

        $this->issue($draft, '20260001');

        $this->assertDatabaseHas(
            'invoices',
            [
                'id' => $draft->id,
                'invoice_vs' => '20260001',
                'issue_date' => self::ISSUE_DATE,
                // An empty tax point date is the issue date
                'tax_point_date' => self::ISSUE_DATE,
                'issued_by' => $this->user->id,
                'is_vat_payer' => true,
                'register_entry' => 'C 12345 vedená u Krajského soudu v Brně',
            ],
        );
        $this->assertNotNull($draft->fresh()->issued_at);
        $this->assertDatabaseHas(
            'vat_lines',
            [
                'invoice_id' => $draft->id,
                'rate' => '21.00',
                'base' => '100.00',
                'vat' => '21.00',
            ],
        );
    }

    #[Test]
    public function a_non_payers_invoice_stores_no_vat_lines(): void
    {
        $this->business->update(['is_vat_payer' => false]);
        $draft = $this->draft('0');

        $this->issue(
            $draft,
            '20260001',
            new DateTimeImmutable('2026-03-01'),
        );

        $issued = $draft->fresh();
        $this->assertFalse($issued->is_vat_payer);
        $this->assertSame(
            '2026-03-01',
            $issued->tax_point_date->format('Y-m-d'),
        );
        $this->assertDatabaseMissing('vat_lines', ['invoice_id' => $draft->id]);
    }

    #[Test]
    public function a_number_of_another_year_is_refused_with_the_next_number_of_the_issue_year(): void
    {
        $this->issuedInvoice('20260004');
        $draft = $this->draft();

        try {
            $this->issue($draft, '20250001');
            $this->fail('The number of another year was accepted');
        } catch (InvoiceNumberYearException $exception) {
            $this->assertSame('20260005', $exception->suggestion->value);
        }
        $this->assertNull($draft->fresh()->issued_at);
    }

    #[Test]
    public function a_taken_number_is_refused_with_the_next_free_number(): void
    {
        $this->issuedInvoice('20260007');
        $draft = $this->draft();

        try {
            $this->issue($draft, '20260007');
            $this->fail('The taken number was accepted');
        } catch (InvoiceNumberTakenException $exception) {
            $this->assertSame('20260008', $exception->suggestion?->value);
        }
        $this->assertNull($draft->fresh()->issued_at);
    }

    #[Test]
    public function the_number_of_another_owner_does_not_count(): void
    {
        $other = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $other->id,
                'invoice_vs' => '20260001',
            ],
        );
        $draft = $this->draft();

        $this->issue($draft, '20260001');

        $this->assertSame('20260001', $draft->fresh()->invoice_vs);
    }

    #[Test]
    public function the_monthly_quota_is_checked_at_issue(): void
    {
        $this->limitFrontendUser(LimitedEntity::INVOICE, 0);
        $draft = $this->draft();

        $this->expectException(EntityLimitExceededException::class);

        $this->issue($draft, '20260001');
    }

    /** @return array<string, array{string}> */
    public static function draftChanges(): array
    {
        return [
            'issue again' => ['issue'],
            'template' => ['template'],
            'link contact' => ['contact'],
            'delete' => ['delete'],
        ];
    }

    #[Test]
    #[DataProvider('draftChanges')]
    public function an_issued_invoice_is_locked(string $change): void
    {
        $issued = $this->issuedInvoice('20260001');
        $id = InvoiceId::fromInt($issued->id);
        $owner = $this->idOf($this->user);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->user->id]);

        $this->expectException(InvoiceIssuedException::class);

        $this->runAs(
            $owner,
            match ($change) {
                'issue' => new IssueInvoice(
                    $owner,
                    $id,
                    new InvoiceNumber('20260002'),
                    new DateTimeImmutable(self::ISSUE_DATE),
                    null,
                ),
                'template' => new SetInvoiceTemplate(
                    $owner,
                    $id,
                    InvoiceTemplate::MODERN,
                ),
                'contact' => new LinkInvoiceContact(
                    $owner,
                    $id,
                    ContactId::fromInt($contact->id),
                ),
                'delete' => new DeleteInvoice($owner, $id),
            },
        );
    }

    #[Test]
    public function a_draft_has_no_payment_status_to_change(): void
    {
        $draft = $this->draft();
        $owner = $this->idOf($this->user);

        $this->expectException(InvoiceNotIssuedException::class);

        $this->runAs(
            $owner,
            new SetInvoiceStatus(
                $owner,
                InvoiceId::fromInt($draft->id),
                InvoiceStatus::PAID,
            ),
        );
    }

    #[Test]
    public function an_issued_invoice_changes_its_payment_status(): void
    {
        $issued = $this->issuedInvoice('20260001');
        $owner = $this->idOf($this->user);

        $this->runAs(
            $owner,
            new SetInvoiceStatus(
                $owner,
                InvoiceId::fromInt($issued->id),
                InvoiceStatus::PAID,
            ),
        );

        $this->assertSame(InvoiceStatus::PAID, $issued->fresh()->payment_status);
    }

    #[Test]
    public function deleting_a_draft_removes_it_with_its_logo_copy(): void
    {
        $disk = Storage::fake('public');
        $disk->put('documents/logos/logo.png', 'logo');
        $draft = $this->draft();
        $draft->update(['supplier_logo' => 'documents/logos/logo.png']);
        $owner = $this->idOf($this->user);

        $this->runAs(
            $owner,
            new DeleteInvoice(
                $owner,
                InvoiceId::fromInt($draft->id),
            ),
        );

        $this->assertDatabaseMissing('invoices', ['id' => $draft->id]);
        $this->assertDatabaseMissing('invoice_products', ['invoice_id' => $draft->id]);
        $disk->assertMissing('documents/logos/logo.png');
    }

    #[Test]
    public function copying_an_issued_invoice_creates_a_draft_with_its_lines(): void
    {
        $source = $this->draft();
        $this->issue($source, '20260001');
        $owner = $this->idOf($this->user);

        $copy = $this->runAs(
            $owner,
            new CopyInvoice(
                $owner,
                InvoiceId::fromInt($source->id),
            ),
        );

        $this->assertNull($copy->issue);
        $this->assertNotSame($source->id, $copy->id->value);
        $this->assertCount(1, $copy->items);
        $this->assertSame('Konzultace', $copy->items[0]->name);
        $this->assertSame($source->customer_name, $copy->parties->customer->name);
        $this->assertSame('20260001', $source->fresh()->invoice_vs);
    }

    #[Test]
    public function the_pdf_of_a_draft_is_named_by_its_id_and_printed_in_the_document_language(): void
    {
        $draft = $this->draft();
        $draft->update(['document_language' => 'de']);
        $owner = $this->idOf($this->user);

        $pdf = $this->runAs(
            $owner,
            new GetInvoicePdf(
                $owner,
                InvoiceId::fromInt($draft->id),
            ),
        );

        $this->assertSame('faktura-' . $draft->id . '.pdf', $pdf->filename);
        $this->assertSame('de', $pdf->locale);
        $this->assertStringStartsWith('%PDF', $pdf->content);
    }

    #[Test]
    public function the_pdf_of_an_issued_invoice_is_named_by_its_number(): void
    {
        $draft = $this->draft();
        $draft->update(['document_language' => 'en']);
        $this->issue($draft, '20260001');
        $owner = $this->idOf($this->user);

        $pdf = $this->runAs(
            $owner,
            new GetInvoicePdf(
                $owner,
                InvoiceId::fromInt($draft->id),
            ),
        );

        $this->assertSame('faktura-20260001.pdf', $pdf->filename);
        $this->assertSame('en', $pdf->locale);
    }

    #[Test]
    public function a_guest_gets_the_first_number_of_the_clocks_year(): void
    {
        $this->app->instance(
            ClockInterface::class,
            new FixedClock(
                new DateTimeImmutable('2031-06-01 12:00:00'),
            ),
        );
        $guestForm = $this->app->make(GuestInvoiceCreateForm::class);

        $form = $guestForm->read();

        $this->assertSame('20310001', $form->suggestedNumber);
    }

    private function draft(string $taxRate = '21'): Invoice
    {
        $invoiceFactory = Invoice::factory();
        $draftFactory = $invoiceFactory->draft();
        $draft = $draftFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $this->business->id,
                'payment_currency' => 'CZK',
            ],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProductFactory->create(
            [
                'invoice_id' => $draft->id,
                'product_id' => null,
                'is_custom_product' => true,
                'name' => 'Konzultace',
                'quantity' => '1',
                'price' => '100',
                'currency' => 'CZK',
                'tax_rate' => $taxRate,
            ],
        );

        return $draft;
    }

    private function issuedInvoice(string $number): Invoice
    {
        $invoiceFactory = Invoice::factory();

        return $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $this->business->id,
                'invoice_vs' => $number,
            ],
        );
    }

    private function issue(
        Invoice $draft,
        string $number,
        ?DateTimeImmutable $taxPointDate = null,
    ): void {
        $owner = $this->idOf($this->user);
        $this->runAs(
            $owner,
            new IssueInvoice(
                $owner,
                InvoiceId::fromInt($draft->id),
                new InvoiceNumber($number),
                new DateTimeImmutable(self::ISSUE_DATE),
                $taxPointDate,
            ),
        );
    }
}
