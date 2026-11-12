<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Invoice;

use App\Livewire\Invoice\InvoiceListRecent;
use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceListRecentFeatureTest extends TestCase
{
    #[Test]
    public function it_shows_the_five_newest_invoices_of_the_user_newest_first(): void
    {
        $user = $this->frontendUser();
        $userFactory = User::factory();
        $other = $userFactory->create();
        $invoiceFactory = Invoice::factory();
        $now = CarbonImmutable::now();
        $numbers = [];
        for ($day = 1; $day <= 6; $day++) {
            $invoice = $invoiceFactory->create(
                [
                    'user_id' => $user->id,
                    'created_at' => $now->subDays($day),
                ],
            );
            $numbers[] = $invoice->invoice_vs;
        }
        $foreign = $invoiceFactory->create(['user_id' => $other->id, 'created_at' => $now]);
        $this->actingAs($user);

        $component = Livewire::test(InvoiceListRecent::class);

        $component->assertSeeInOrder(
            array_slice(
                $numbers,
                0,
                5,
            ),
        );
        $component->assertDontSee($numbers[5]);
        $component->assertDontSee($foreign->invoice_vs);
    }

    #[Test]
    public function it_shows_the_empty_state_without_invoices(): void
    {
        $this->actingAs($this->frontendUser());

        $component = Livewire::test(InvoiceListRecent::class);

        $component->assertSee(
            __('dashboard.status.no_invoices'),
        );
    }

    #[Test]
    public function it_needs_the_invoice_view_permission(): void
    {
        $userFactory = User::factory();
        $this->actingAs($userFactory->create());

        $component = Livewire::test(InvoiceListRecent::class);

        $component->assertForbidden();
    }

    private function frontendUser(): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole('frontend_user');

        return $user;
    }
}
