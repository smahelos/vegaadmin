<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\Analytics\Contracts\DashboardServiceInterface;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Analytics\DTO\ClientTotalDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use App\Domain\User\ValueObjects\UserId;
use Tests\TestCase;

class DashboardControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $locale = config('app.locale');
        $response = $this->get(route('frontend.dashboard', ['locale' => $locale]));
        $response->assertStatus(302);
        // Login route outside locale group -> no locale param expected
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_sees_dashboard_with_expected_variables(): void
    {
        // Bind fake service implementation
        $this->app->bind(DashboardServiceInterface::class, function () {
            return new class implements DashboardServiceInterface {
                public function getUserStatistics(UserId $userId): UserStatisticsDTO
                {
                    return new UserStatisticsDTO(
                        invoiceCount: 5,
                        clientCount: 3,
                        suppliersCount: 2,
                        totalAmount: Money::fromString('123.45', 'CZK')
                    );
                }
                public function getMonthlyStatistics(UserId $userId, int $months = 6): array
                {
                    return [
                        new MonthlyStatDTO('2025-01', Money::fromString('10.00', 'CZK')),
                    ];
                }
                public function getClientsWithInvoiceTotals(UserId $userId): array
                {
                    return [
                        new ClientTotalDTO(1, 'Client A', Money::fromString('50.00', 'CZK')),
                    ];
                }
                public function getDashboardData(UserId $userId): array
                {
                    return [
                        'statistics_formatted' => [
                            'invoice_count' => 5,
                            'client_count' => 3,
                            'suppliers_count' => 2,
                            'total_amount' => '123.45'
                        ],
                        'monthly_stats' => $this->getMonthlyStatistics($userId),
                        'clients' => $this->getClientsWithInvoiceTotals($userId)
                    ];
                }
                public function invalidateUserCache(int $userId): bool { return true; }
            };
        });

        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        // Ensure permissions referenced in navigation exist to avoid rendering exceptions
        foreach ([
            'frontend.can_create_edit_client',
            'frontend.can_create_edit_supplier',
            'frontend.can_create_edit_product',
        ] as $perm) {
            \Spatie\Permission\Models\Permission::findOrCreate($perm, 'web');
        }

        $locale = config('app.locale');
        $response = $this->get(route('frontend.dashboard', ['locale' => $locale]));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.dashboard');
        $response->assertViewHasAll([
            'invoiceCount', 'clientCount', 'suppliersCount', 'totalAmount', 'monthlyStats', 'clients'
        ]);
    }
}
