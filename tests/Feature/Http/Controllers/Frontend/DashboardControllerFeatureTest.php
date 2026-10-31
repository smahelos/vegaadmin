<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class DashboardControllerFeatureTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $this->get(route('frontend.dashboard', ['locale' => 'cs']))->assertRedirect(route('login'));
    }

    #[Test]
    public function the_dashboard_renders_the_users_statistics_and_latest_parties(): void
    {
        $user = $this->frontendUser();
        $business = Business::factory()->create(['user_id' => $user->id, 'name' => 'My own business']);
        Contact::factory()->create(['user_id' => $user->id, 'name' => 'My contact']);
        Contact::factory()->create(['name' => 'Private other contact']);
        Invoice::factory()->create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'payment_amount' => '123.45',
        ]);

        $this->actingAs($user)->get(route('frontend.dashboard', ['locale' => 'cs']))
            ->assertOk()->assertSee('My own business')->assertSee('My contact')->assertDontSee('Private other contact')
            ->assertViewHas('dashboard', fn ($dashboard): bool => $dashboard->statistics->invoiceCount === 1
                && $dashboard->statistics->businessCount === 1
                && $dashboard->statistics->contactCount === 1
                && $dashboard->statistics->totalAmount->getAmount() === '123.45');
    }

    #[Test]
    public function one_off_invoices_keep_the_date_filters_available_without_a_contact_selector(): void
    {
        $user = $this->frontendUser();
        Invoice::factory()->create(['user_id' => $user->id, 'contact_id' => null]);

        $this->actingAs($user)->get(route('frontend.dashboard', ['locale' => 'cs']))
            ->assertOk()
            ->assertSee('id="stats-timerange"', false)
            ->assertSee('id="stats-date-from"', false)
            ->assertDontSee('id="stats-contact-filter"', false)
            ->assertViewHas('dashboard', fn ($dashboard): bool => $dashboard->contacts === []);
    }
}
