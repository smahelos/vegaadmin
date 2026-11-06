<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Invoice;

use App\Livewire\Invoice\InvoiceList;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class InvoiceListFeatureTest extends TestCase
{
    use BuildsParties;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->frontendUser();
        $this->actingAs($this->user);
    }

    #[Test]
    public function shows_only_owned_invoices_and_filter_options(): void
    {
        $invoiceFactory = Invoice::factory();
        $owned = $invoiceFactory->create(['user_id' => $this->user->id, 'invoice_vs' => 'OWNED-001']);
        $foreign = $invoiceFactory->create(['invoice_vs' => 'FOREIGN-001']);
        $contactFactory = Contact::factory();
        $foreignContact = $contactFactory->create(['name' => 'Foreign Contact']);
        $component = Livewire::test(InvoiceList::class);
        $component->assertSee($owned->invoice_vs);
        $component->assertDontSee($foreign->invoice_vs);
        $component->assertDontSee($foreignContact->name);
        $invoices = $component->viewData('invoices');
        $invoiceIds = $invoices->map(fn ($row): int => $row->id->value);
        $this->assertSame(
            [$owned->id],
            $invoiceIds->all(),
        );
        $this->assertNotContains(
            $foreign->business_id,
            array_map(
                fn ($row): int => $row->id->value,
                $component->viewData('businesses'),
            ),
        );
    }

    #[Test]
    public function combines_business_contact_status_and_stored_customer_search(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);
        $other = $businessFactory->create(['user_id' => $this->user->id]);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            ['user_id' => $this->user->id, 'name' => 'Current Address Book Name'],
        );
        $statusQuery = Status::where('slug', 'paid');
        $paid = $statusQuery->value('id');
        $statusQuery = Status::where('slug', 'pending');
        $pending = $statusQuery->value('id');
        $base = [
            'user_id' => $this->user->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'payment_status_id' => $paid,
            'customer_name' => 'Stored Customer',
        ];
        $invoiceFactory = Invoice::factory();
        $match = $invoiceFactory->create($base + ['invoice_vs' => 'MATCH-001']);
        $invoiceFactory->create(
            array_replace(
                $base,
                ['business_id' => $other->id, 'invoice_vs' => 'OTHER-BUSINESS'],
            ),
        );
        $invoiceFactory->create(
            array_replace($base, ['contact_id' => null, 'invoice_vs' => 'ONE-OFF']),
        );
        $invoiceFactory->create(
            array_replace($base, ['payment_status_id' => $pending, 'invoice_vs' => 'UNPAID']),
        );
        $invoiceFactory->create(
            array_replace(
                $base,
                ['customer_name' => 'Different Customer', 'invoice_vs' => 'OTHER-NAME'],
            ),
        );

        Livewire::withQueryParams(
            ['business_id' => (string) $business->id, 'contact_id' => (string) $contact->id],
        );
        $component = Livewire::test(InvoiceList::class);
        $component->set('status', 'paid');
        $component->set('search', 'Stored Customer');
        $component->assertSee('MATCH-001');
        $component->assertDontSee('OTHER-BUSINESS');
        $component->assertDontSee('ONE-OFF');
        $component->assertDontSee('UNPAID');
        $component->assertDontSee('OTHER-NAME');
        $invoices = $component->viewData('invoices');
        $this->assertSame(
            1,
            $invoices->total(),
        );
        $this->assertSame(
            $match->id,
            $invoices->first()->id->value,
        );
    }

    #[Test]
    public function filters_each_party_independently_and_keeps_one_off_history(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);
        $other = $businessFactory->create(['user_id' => $this->user->id]);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'invoice_vs' => 'FIRST',
            ],
        );
        $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $other->id,
                'contact_id' => $contact->id,
                'invoice_vs' => 'SECOND',
            ],
        );
        $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $business->id,
                'contact_id' => null,
                'invoice_vs' => 'ONE-OFF',
            ],
        );
        $component = Livewire::test(InvoiceList::class);
        $component->set('businessId', (string) $business->id);
        $component->assertSee('FIRST');
        $component->assertSee('ONE-OFF');
        $component->assertDontSee('SECOND');
        $component->set('businessId', '');
        $component->set('contactId', (string) $contact->id);
        $component->assertSee('FIRST');
        $component->assertSee('SECOND');
        $component->assertDontSee('ONE-OFF');
    }

    #[Test]
    public function archived_parties_remain_filterable_without_restoration(): void
    {
        $businessFactory = Business::factory();
        $archivedBusinessFactory = $businessFactory->archived();
        $business = $archivedBusinessFactory->create(['user_id' => $this->user->id]);
        $contactFactory = Contact::factory();
        $archivedContactFactory = $contactFactory->archived();
        $contact = $archivedContactFactory->create(['user_id' => $this->user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'invoice_vs' => 'HISTORY-001',
            ],
        );
        $component = Livewire::test(InvoiceList::class);
        $component->set('businessId', (string) $business->id);
        $component->set('contactId', (string) $contact->id);
        $component->assertSee('HISTORY-001');
        $component->assertSee(
            __('invoices.filters.archived'),
        );
        $this->assertNotNull($business->fresh()->archived_at);
        $this->assertNotNull($contact->fresh()->archived_at);
    }

    #[Test]
    public function rejects_foreign_and_missing_business_ids_identically(): void
    {
        $businessFactory = Business::factory();
        $foreign = $businessFactory->create();
        foreach ([(string) $foreign->id, '2147483647'] as $id) {
            $component = Livewire::test(InvoiceList::class);
            $component->set('businessId', $id);
            $component->assertNotFound();
        }
    }

    #[Test]
    public function rejects_foreign_and_missing_contact_ids_identically(): void
    {
        $contactFactory = Contact::factory();
        $foreign = $contactFactory->create();
        foreach ([(string) $foreign->id, '2147483647'] as $id) {
            $component = Livewire::test(InvoiceList::class);
            $component->set('contactId', $id);
            $component->assertNotFound();
        }
    }

    #[Test]
    #[DataProvider('invalidFilters')]
    public function rejects_malformed_url_filters(string $key, string $value): void
    {
        Livewire::withQueryParams([$key => $value]);
        $component = Livewire::test(InvoiceList::class);
        $component->assertNotFound();
    }

    /** @return list<array{string, string}> */
    public static function invalidFilters(): array
    {
        return [
            ['business_id', '0'],
            ['business_id', '1 OR 1=1'],
            ['contact_id', '-1'],
            ['contact_id', '999999999999999999999'],
            ['status', 'unknown'],
        ];
    }

    #[Test]
    public function filter_changes_and_reset_return_to_the_first_page(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory = $invoiceFactory->count(12);
        $statusQuery = Status::where('slug', 'paid');
        $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'invoice_vs' => 'PAGE-001',
                'payment_status_id' => $statusQuery->value('id'),
            ],
        );
        $component = Livewire::test(InvoiceList::class);
        foreach (
            [
                'businessId' => (string) $business->id,
                'contactId' => (string) $contact->id,
                'search' => 'PAGE',
                'status' => 'paid',
            ] as $key => $value
        ) {
            $component->call('gotoPage', 2);
            $component->set($key, $value);
            $component->assertSet('paginators.page', 1);
        }
        $component->call('gotoPage', 2);
        $component->call('resetFilters');
        $component->assertSet('paginators.page', 1);
        $component->assertSet('businessId', '');
        $component->assertSet('contactId', '');
        $component->assertSet('search', '');
        $component->assertSet('status', '');
        $invoices = $component->viewData('invoices');
        $this->assertSame(
            12,
            $invoices->total(),
        );
    }

    #[Test]
    public function empty_results_keep_filter_controls_available(): void
    {
        $component = Livewire::test(InvoiceList::class);
        $component->set('search', 'No match');
        $component->assertSeeHtml('id="invoice-business"');
        $component->assertSeeHtml('id="invoice-contact"');
        $component->assertViewHas('hasData', false);
        $component->call('resetFilters');
        $component->assertSet('search', '');
    }

    #[Test]
    public function sorts_due_dates_and_ignores_unknown_sort_columns(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'invoice_vs' => 'EARLY',
                'issue_date' => '2026-01-10',
                'due_in' => 5,
                'created_at' => '2026-01-01',
            ],
        );
        $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'invoice_vs' => 'LATER',
                'issue_date' => '2026-01-01',
                'due_in' => 30,
                'created_at' => '2026-01-02',
            ],
        );
        Livewire::withQueryParams(['sort' => 'user_id desc; --']);
        $component = Livewire::test(InvoiceList::class);
        $component->assertSeeInOrder(['LATER', 'EARLY']);
        $component->call('sortBy', 'due_date');
        $component->assertSeeInOrder(['EARLY', 'LATER']);
        $component->call('sortBy', 'due_date');
        $component->assertSeeInOrder(['LATER', 'EARLY']);
    }

    #[Test]
    public function searches_invoice_numbers_and_links_stored_customers_to_contacts(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'contact_id' => $contact->id,
                'invoice_vs' => 'LINKED-001',
                'customer_name' => 'Stored Customer',
            ],
        );
        $invoiceFactory->create(['user_id' => $this->user->id, 'invoice_vs' => 'OTHER-002']);
        $component = Livewire::test(InvoiceList::class);
        $component->set('search', 'LINKED-001');
        $component->assertSee('Stored Customer');
        $component->assertDontSee('OTHER-002');
        $application = app();
        $component->assertSeeHtml(
            'href="' . route(
                'frontend.contacts.show',
                ['locale' => $application->getLocale(), 'contact' => $contact->id],
            ) . '"',
        );
    }

    #[Test]
    public function rejects_guests(): void
    {
        $auth = auth();
        $auth->logout();
        $component = Livewire::test(InvoiceList::class);
        $component->assertForbidden();
    }

    #[Test]
    public function rejects_a_user_without_invoice_view_permission(): void
    {
        $userFactory = User::factory();
        $this->actingAs($userFactory->create());
        $component = Livewire::test(InvoiceList::class);
        $component->assertForbidden();
    }
}
