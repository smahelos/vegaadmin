<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Application\Analytics\DTO\AdminDashboardDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class AdminDashboardPartiesTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    #[Test]
    public function dashboard_counts_only_active_businesses_and_contacts_and_links_to_business_creation(): void
    {
        $this->setUpAdminTestEnvironment();
        $businessFactory = Business::factory();
        $businessFactory->create(['user_id' => $this->regularUser->id]);
        $businessFactory->create(
            ['user_id' => $this->regularUser->id, 'archived_at' => now()],
        );
        $contactFactory = Contact::factory();
        $contactFactory->create(['user_id' => $this->regularUser->id]);
        $contactFactory->create(
            ['user_id' => $this->regularUser->id, 'archived_at' => now()],
        );
        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $httpResponse = $httpClient->get('/admin/dashboard');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertViewHas(
            'dashboard',
            fn (AdminDashboardDTO $dashboard): bool => $dashboard->statistics->businessCount === 1
                && $dashboard->statistics->contactCount === 1
                && $dashboard->statistics->recentContactCount === 1,
        );
        $httpResponse = $httpResponse->assertSee(
            backpack_url('business/create'),
        );
        $httpResponse->assertDontSee(
            backpack_url('client/create'),
        );
    }

    #[Test]
    public function revenue_is_summed_per_currency(): void
    {
        $this->setUpAdminTestEnvironment();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '100.50',
                'payment_currency' => 'CZK',
            ],
        );
        $invoiceFactory->create(
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '200.25',
                'payment_currency' => 'CZK',
            ],
        );
        $invoiceFactory->create(
            [
                'user_id' => $this->regularUser->id,
                'payment_amount' => '10.00',
                'payment_currency' => 'EUR',
            ],
        );
        $httpClient = $this->actingAs($this->adminUser, 'backpack');
        $httpResponse = $httpClient->get('/admin/dashboard');

        $httpResponse->assertViewHas(
            'dashboard',
            fn (AdminDashboardDTO $dashboard): bool => $this->sums($dashboard->statistics->revenue) === ['CZK 300.75', 'EUR 10'],
        );
    }

    #[Test]
    public function the_dashboard_opens_without_statistics_for_an_admin_without_the_permission(): void
    {
        $this->setUpAdminTestEnvironment();
        $httpClient = $this->actingAs($this->regularUser, 'backpack');
        $httpResponse = $httpClient->get('/admin/dashboard');

        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertViewHas('dashboard', null);
        $httpResponse->assertDontSee(
            __('admin.dashboard.revenue_overview'),
        );
    }

    /**
     * @param  list<Money>  $revenue
     * @return list<string>
     */
    private function sums(array $revenue): array
    {
        return array_map(
            fn (Money $amount): string => $amount->currency . ' ' . $amount->amount,
            $revenue,
        );
    }
}
