<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\DashboardController;
use App\Application\Analytics\Contracts\DashboardApplicationServiceInterface;
use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Analytics\DTO\ClientTotalDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\User;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for DashboardController focusing on pure controller logic contract.
 * No middleware / routing integration – covered by feature tests.
 */
class DashboardControllerTest extends TestCase
{
    #[Test]
    public function index_returns_view_with_expected_keys(): void
    {
        // Arrange fake service returning deterministic data
        $service = new class implements DashboardApplicationServiceInterface {
            public function getUserStatistics(User $user): UserStatisticsDTO
            {
                return new UserStatisticsDTO(1, 2, 3, Money::fromString('10.00', 'CZK'));
            }
            public function getMonthlyStatistics(User $user, int $months = 6): Collection
            {
                return collect([new MonthlyStatDTO('2025-01', Money::fromString('5.00', 'CZK'))]);
            }
            public function getClientsWithInvoiceTotals(User $user): Collection
            {
                return collect([new ClientTotalDTO(1, 'Client', Money::fromString('5.00', 'CZK'))]);
            }
            public function getDashboardData(User $user): array
            {
                return [
                    'statistics_formatted' => [
                        'invoice_count' => 1,
                        'client_count' => 2,
                        'suppliers_count' => 3,
                        'total_amount' => '10.00'
                    ],
                    'monthly_stats' => $this->getMonthlyStatistics($user),
                    'clients' => $this->getClientsWithInvoiceTotals($user),
                ];
            }
            public function invalidateUserCache(User $user): bool { return true; }
        };

        $controller = new DashboardController($service);
    // Act
    // We cannot easily mock Auth::user() here without the framework; skip invocation that relies on Auth facade.
    // Instead we just assert the controller method exists and has zero parameters via reflection, then simulate output mapping logic.
    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('index');
    $this->assertSame(0, $method->getNumberOfParameters());

    // Manually call index() after faking an authenticated user via actingAs
    $fakeUser = User::factory()->make();
    $this->be($fakeUser, 'web');
    $response = $controller->index();

        // Assert
        $this->assertInstanceOf(ViewContract::class, $response);
    $data = (array) $response->getData();
    $this->assertArrayHasKey('invoiceCount', $data);
    $this->assertArrayHasKey('clientCount', $data);
    $this->assertArrayHasKey('suppliersCount', $data);
    $this->assertArrayHasKey('totalAmount', $data);
    $this->assertArrayHasKey('monthlyStats', $data);
    $this->assertArrayHasKey('clients', $data);
    }
}
