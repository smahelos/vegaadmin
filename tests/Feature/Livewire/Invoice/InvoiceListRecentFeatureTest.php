<?php

namespace Tests\Feature\Livewire\Invoice;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Livewire\Invoice\InvoiceListRecent;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceListRecentFeatureTest extends TestCase
{
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

        $userInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $otherUserInvoice = Invoice::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);
        $invoices = $component->viewData('invoices');

        $this->assertEquals(1, $invoices->count());
        $this->assertEquals($userInvoice->id, $invoices->first()->id);
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

        $component->assertViewIs('livewire.invoice.invoice-list-recent');
    }

    #[Test]
    public function component_passes_correct_data_to_view(): void
    {
        Invoice::factory()->create([
            'user_id' => $this->user->id,
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
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $paymentStatus = Status::factory()->create();

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $paymentStatus->id,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(InvoiceListRecent::class);
        $invoices = $component->viewData('invoices');

        // Check that relationships are loaded
        $invoice = $invoices->first();
        $this->assertTrue($invoice->relationLoaded('paymentMethod'));
        $this->assertTrue($invoice->relationLoaded('paymentStatus'));
    }

    #[Test]
    public function component_defaults_to_descending_created_at_order(): void
    {
        $oldInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);

        $newInvoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
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
        // Create 7 invoices
        Invoice::factory()->count(7)->create([
            'user_id' => $this->user->id,
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
