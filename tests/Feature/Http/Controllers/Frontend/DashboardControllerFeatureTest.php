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
        $httpResponse = $this->get(
            route('frontend.dashboard', ['locale' => 'cs']),
        );
        $httpResponse->assertRedirect(
            route('login'),
        );
    }

    #[Test]
    public function the_dashboard_renders_the_users_statistics_and_latest_parties(): void
    {
        $user = $this->frontendUser();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $user->id, 'name' => 'My own business']);
        $contactFactory = Contact::factory();
        $contactFactory->create(['user_id' => $user->id, 'name' => 'My contact']);
        $contactFactory2 = Contact::factory();
        $contactFactory2->create(['name' => 'Private other contact']);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'business_id' => $business->id,
                'payment_amount' => '123.45',
            ],
        );

        $httpClient = $this->actingAs($user);
        $httpResponse = $httpClient->get(
            route('frontend.dashboard', ['locale' => 'cs']),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('My own business');
        $httpResponse = $httpResponse->assertSee('My contact');
        $httpResponse = $httpResponse->assertDontSee('Private other contact');
        $httpResponse->assertViewHas(
            'dashboard',
            fn ($dashboard): bool => $dashboard->statistics->invoiceCount === 1
            && $dashboard->statistics->businessCount === 1
            && $dashboard->statistics->contactCount === 1
            && $dashboard->statistics->totalAmount->getAmount() === '123.45',
        );
    }

    #[Test]
    public function one_off_invoices_keep_the_date_filters_available_without_a_contact_selector(): void
    {
        $user = $this->frontendUser();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(['user_id' => $user->id, 'contact_id' => null]);

        $httpClient = $this->actingAs($user);
        $httpResponse = $httpClient->get(
            route('frontend.dashboard', ['locale' => 'cs']),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('id="stats-timerange"', false);
        $httpResponse = $httpResponse->assertSee('id="stats-date-from"', false);
        $httpResponse = $httpResponse->assertDontSee('id="stats-contact-filter"', false);
        $httpResponse->assertViewHas('dashboard', fn ($dashboard): bool => $dashboard->contacts === []);
    }
}
