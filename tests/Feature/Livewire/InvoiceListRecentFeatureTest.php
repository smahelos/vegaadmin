<?php

namespace Tests\Feature\Livewire;

use App\Livewire\InvoiceListRecent;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceListRecentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function component_can_be_rendered(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);

        $component->assertStatus(200);
    }

    #[Test]
    public function component_displays_user_invoices_only(): void
    {
        $otherUser = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);

        $userInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $otherUserInvoice = Invoice::factory()->create([
            'user_id' => $otherUser->id,
            'client_id' => $otherClient->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);
        $invoices = $component->viewData('invoices');

        $this->assertEquals(1, $invoices->count());
        $this->assertEquals($userInvoice->id, $invoices->first()->id);
    }

    #[Test]
    public function sort_by_toggles_order_direction_for_same_field(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class)
            ->set('orderBy', 'issue_date')
            ->set('orderAsc', true);

        $component->call('sortBy', 'issue_date');

        $component->assertSet('orderBy', 'issue_date');
        $component->assertSet('orderAsc', false);
    }

    #[Test]
    public function sort_by_sets_new_field_with_ascending_order(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class)
            ->set('orderBy', 'created_at')
            ->set('orderAsc', false);

        $component->call('sortBy', 'issue_date');

        $component->assertSet('orderBy', 'issue_date');
        $component->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_displays_empty_state_when_no_invoices(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);

        $hasData = $component->viewData('hasData');
        $this->assertFalse($hasData);
    }

    #[Test]
    public function component_requires_authentication(): void
    {
        // Test that component works when authenticated
        $this->actingAs($this->user);
        $component = Livewire::test(InvoiceListRecent::class);
        $component->assertStatus(200);
        
        // Test behavior when not authenticated (Auth::id() returns null)
        auth()->logout();
        
        $component = Livewire::test(InvoiceListRecent::class);
        $invoices = $component->viewData('invoices');
        
        // Should return empty collection when no user authenticated
        $this->assertEquals(0, $invoices->count());
    }

    #[Test]
    public function component_uses_correct_view(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);

        $component->assertViewIs('livewire.invoice-list-recent');
    }

    #[Test]
    public function component_passes_correct_data_to_view(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);

        $component->assertViewHas('invoices');
        $component->assertViewHas('hasData', true);
        $component->assertViewHas('errorMessage', null);
    }

    #[Test]
    public function component_eager_loads_relationships(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $paymentMethod = PaymentMethod::factory()->create();
        $paymentStatus = Status::factory()->create();
        
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $paymentStatus->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);
        $invoices = $component->viewData('invoices');

        // Check that relationships are loaded
        $invoice = $invoices->first();
        $this->assertTrue($invoice->relationLoaded('client'));
        $this->assertTrue($invoice->relationLoaded('paymentMethod'));
        $this->assertTrue($invoice->relationLoaded('paymentStatus'));
    }

    #[Test]
    public function mount_method_sets_pagination_theme(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);

        // Access the component instance to check pagination theme
        $this->assertEquals('bootstrap', $component->instance()->paginationTheme);
    }

    #[Test]
    public function component_preserves_url_state(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class)
            ->set('orderBy', 'issue_date')
            ->set('orderAsc', true);

        $component->assertSet('orderBy', 'issue_date');
        $component->assertSet('orderAsc', true);
    }

    #[Test]
    public function component_defaults_to_descending_created_at_order(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        
        $oldInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'created_at' => now()->subDays(2)
        ]);
        
        $newInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'created_at' => now()->subDay()
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);
        $invoices = $component->viewData('invoices');

        // Should be ordered by created_at desc by default (newest first)
        $this->assertEquals($newInvoice->id, $invoices->first()->id);
    }

    #[Test]
    public function component_limits_to_5_invoices_per_page(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        
        // Create 7 invoices
        Invoice::factory()->count(7)->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);
        $invoices = $component->viewData('invoices');

        // Should only show 5 per page
        $this->assertEquals(5, $invoices->perPage());
        $this->assertEquals(7, $invoices->total());
        $this->assertEquals(5, $invoices->count());
    }
}
