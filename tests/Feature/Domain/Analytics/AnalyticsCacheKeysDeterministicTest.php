<?php

namespace Tests\Feature\Domain\Analytics;

use App\Domain\Analytics\Contracts\DashboardServiceInterface;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsCacheKeysDeterministicTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dashboard_service_uses_deterministic_cache_keys(): void
    {
        $user = User::factory()->create();
        // Seed some minimal data to ensure aggregation produces non-null values
        $client = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['user_id' => $user->id,'client_id' => $client->id,'payment_amount' => 123.45,'issue_date' => now()->subMonth()]);

        $service = app(DashboardServiceInterface::class);

        // First calls (should populate cache)
    $uid = UserId::fromInt($user->id);
    $service->getUserStatistics($uid);
    $service->getMonthlyStatistics($uid, 6);
    $service->getClientsWithInvoiceTotals($uid);

        $expectedKeys = [
            'analytics:user:' . $user->id . ':stats',
            'analytics:user:' . $user->id . ':monthly:6',
            'analytics:user:' . $user->id . ':clients',
        ];

        // Validate keys through their tag sets (Redis tagged cache separation)
        $this->assertNotNull(Cache::tags(['analytics','analytics.user.' . $user->id])->get('analytics:user:' . $user->id . ':stats'));
        $this->assertNotNull(Cache::tags(['analytics','analytics.user.' . $user->id,'analytics.monthly'])->get('analytics:user:' . $user->id . ':monthly:6'));
        $this->assertNotNull(Cache::tags(['analytics','analytics.user.' . $user->id,'analytics.clients'])->get('analytics:user:' . $user->id . ':clients'));

        // Second calls (should hit cache). We re-fetch and ensure values identical.
    $stats1 = $service->getUserStatistics($uid)->toArray();
    $stats2 = $service->getUserStatistics($uid)->toArray();
        $this->assertEquals($stats1, $stats2, 'User statistics should be consistent between cached calls.');

        $normalizeMonthly = fn($col) => collect($col)->map(fn($dto) => [$dto->month, $dto->total->getAmount(), $dto->total->getCurrency()])->toArray();
    $monthly1 = $normalizeMonthly($service->getMonthlyStatistics($uid, 6));
    $monthly2 = $normalizeMonthly($service->getMonthlyStatistics($uid, 6));
        $this->assertEquals($monthly1, $monthly2, 'Monthly statistics collection should be consistent between cached calls.');
    }
}
