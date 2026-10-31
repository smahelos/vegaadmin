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
        $owned = Invoice::factory()->create(['user_id' => $this->user->id, 'invoice_vs' => 'OWNED-001']);
        $foreign = Invoice::factory()->create(['invoice_vs' => 'FOREIGN-001']);
        $foreignContact = Contact::factory()->create(['name' => 'Foreign Contact']);
        $component = Livewire::test(InvoiceList::class)
            ->assertSee($owned->invoice_vs)->assertDontSee($foreign->invoice_vs)
            ->assertDontSee($foreignContact->name);
        $this->assertSame([$owned->id], $component->viewData('invoices')->map(fn ($row): int => $row->id->value)->all());
        $this->assertNotContains($foreign->business_id, array_map(fn ($row): int => $row->id->value, $component->viewData('businesses')));
    }

    #[Test]
    public function combines_business_contact_status_and_stored_customer_search(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);
        $other = Business::factory()->create(['user_id' => $this->user->id]);
        $contact = Contact::factory()->create(['user_id' => $this->user->id, 'name' => 'Current Address Book Name']);
        $paid = Status::where('slug', 'paid')->value('id');
        $pending = Status::where('slug', 'pending')->value('id');
        $base = [
            'user_id' => $this->user->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'payment_status_id' => $paid,
            'customer_name' => 'Stored Customer',
        ];
        $match = Invoice::factory()->create($base + ['invoice_vs' => 'MATCH-001']);
        Invoice::factory()->create(array_replace($base, ['business_id' => $other->id, 'invoice_vs' => 'OTHER-BUSINESS']));
        Invoice::factory()->create(array_replace($base, ['contact_id' => null, 'invoice_vs' => 'ONE-OFF']));
        Invoice::factory()->create(array_replace($base, ['payment_status_id' => $pending, 'invoice_vs' => 'UNPAID']));
        Invoice::factory()->create(array_replace($base, ['customer_name' => 'Different Customer', 'invoice_vs' => 'OTHER-NAME']));

        $component = Livewire::withQueryParams(['business_id' => (string) $business->id, 'contact_id' => (string) $contact->id])
            ->test(InvoiceList::class)->set('status', 'paid')->set('search', 'Stored Customer')
            ->assertSee('MATCH-001')->assertDontSee('OTHER-BUSINESS')->assertDontSee('ONE-OFF')
            ->assertDontSee('UNPAID')->assertDontSee('OTHER-NAME');
        $this->assertSame(1, $component->viewData('invoices')->total());
        $this->assertSame($match->id, $component->viewData('invoices')->first()->id->value);
    }

    #[Test]
    public function filters_each_party_independently_and_keeps_one_off_history(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);
        $other = Business::factory()->create(['user_id' => $this->user->id]);
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'invoice_vs' => 'FIRST',
        ]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $other->id,
            'contact_id' => $contact->id,
            'invoice_vs' => 'SECOND',
        ]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $business->id,
            'contact_id' => null,
            'invoice_vs' => 'ONE-OFF',
        ]);
        Livewire::test(InvoiceList::class)->set('businessId', (string) $business->id)
            ->assertSee('FIRST')->assertSee('ONE-OFF')->assertDontSee('SECOND')
            ->set('businessId', '')->set('contactId', (string) $contact->id)
            ->assertSee('FIRST')->assertSee('SECOND')->assertDontSee('ONE-OFF');
    }

    #[Test]
    public function archived_parties_remain_filterable_without_restoration(): void
    {
        $business = Business::factory()->archived()->create(['user_id' => $this->user->id]);
        $contact = Contact::factory()->archived()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'invoice_vs' => 'HISTORY-001',
        ]);
        Livewire::test(InvoiceList::class)->set('businessId', (string) $business->id)->set('contactId', (string) $contact->id)
            ->assertSee('HISTORY-001')->assertSee(__('invoices.filters.archived'));
        $this->assertNotNull($business->fresh()->archived_at);
        $this->assertNotNull($contact->fresh()->archived_at);
    }

    #[Test]
    public function rejects_foreign_and_missing_business_ids_identically(): void
    {
        $foreign = Business::factory()->create();
        foreach ([(string) $foreign->id, '2147483647'] as $id) {
            Livewire::test(InvoiceList::class)->set('businessId', $id)->assertNotFound();
        }
    }

    #[Test]
    public function rejects_foreign_and_missing_contact_ids_identically(): void
    {
        $foreign = Contact::factory()->create();
        foreach ([(string) $foreign->id, '2147483647'] as $id) {
            Livewire::test(InvoiceList::class)->set('contactId', $id)->assertNotFound();
        }
    }

    #[Test]
    #[DataProvider('invalidFilters')]
    public function rejects_malformed_url_filters(string $key, string $value): void
    {
        Livewire::withQueryParams([$key => $value])->test(InvoiceList::class)->assertNotFound();
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
        $business = Business::factory()->create(['user_id' => $this->user->id]);
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(12)->create([
            'user_id' => $this->user->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'invoice_vs' => 'PAGE-001',
            'payment_status_id' => Status::where('slug', 'paid')->value('id'),
        ]);
        $component = Livewire::test(InvoiceList::class);
        foreach ([
            'businessId' => (string) $business->id,
            'contactId' => (string) $contact->id,
            'search' => 'PAGE',
            'status' => 'paid',
        ] as $key => $value) {
            $component->call('gotoPage', 2)->set($key, $value)->assertSet('paginators.page', 1);
        }
        $component->call('gotoPage', 2)->call('resetFilters')->assertSet('paginators.page', 1)
            ->assertSet('businessId', '')->assertSet('contactId', '')->assertSet('search', '')->assertSet('status', '');
        $this->assertSame(12, $component->viewData('invoices')->total());
    }

    #[Test]
    public function empty_results_keep_filter_controls_available(): void
    {
        Livewire::test(InvoiceList::class)->set('search', 'No match')
            ->assertSeeHtml('id="invoice-business"')->assertSeeHtml('id="invoice-contact"')
            ->assertViewHas('hasData', false)->call('resetFilters')->assertSet('search', '');
    }

    #[Test]
    public function sorts_due_dates_and_ignores_unknown_sort_columns(): void
    {
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'invoice_vs' => 'EARLY',
            'issue_date' => '2026-01-10',
            'due_in' => 5,
            'created_at' => '2026-01-01',
        ]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'invoice_vs' => 'LATER',
            'issue_date' => '2026-01-01',
            'due_in' => 30,
            'created_at' => '2026-01-02',
        ]);
        Livewire::withQueryParams(['sort' => 'user_id desc; --'])->test(InvoiceList::class)
            ->assertSeeInOrder(['LATER', 'EARLY'])->call('sortBy', 'due_date')->assertSeeInOrder(['EARLY', 'LATER'])
            ->call('sortBy', 'due_date')->assertSeeInOrder(['LATER', 'EARLY']);
    }

    #[Test]
    public function searches_invoice_numbers_and_links_stored_customers_to_contacts(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'contact_id' => $contact->id,
            'invoice_vs' => 'LINKED-001',
            'customer_name' => 'Stored Customer',
        ]);
        Invoice::factory()->create(['user_id' => $this->user->id, 'invoice_vs' => 'OTHER-002']);
        Livewire::test(InvoiceList::class)->set('search', 'LINKED-001')->assertSee('Stored Customer')->assertDontSee('OTHER-002')
            ->assertSeeHtml('href="' . route('frontend.contacts.show', ['locale' => app()->getLocale(), 'contact' => $contact->id]) . '"');
    }

    #[Test]
    public function rejects_guests(): void
    {
        auth()->logout();
        Livewire::test(InvoiceList::class)->assertForbidden();
    }

    #[Test]
    public function rejects_a_user_without_invoice_view_permission(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(InvoiceList::class)->assertForbidden();
    }
}
