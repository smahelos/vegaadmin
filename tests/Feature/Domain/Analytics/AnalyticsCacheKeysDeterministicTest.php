<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Analytics;

use App\Domain\Analytics\Services\DashboardService;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AnalyticsCacheKeysDeterministicTest extends TestCase
{
    #[Test]
    public function dashboard_service_uses_deterministic_cache_keys(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        // Seed some minimal data to ensure aggregation produces non-null values
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $user->id]);
        $invoiceFactory = Invoice::factory();
        $now = CarbonImmutable::now();
        $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'payment_amount' => 123.45,
                'issue_date' => $now->subMonth(),
            ],
        );

        $service = app(DashboardService::class);

        // First calls (should populate cache)
        $uid = UserId::fromInt($user->id);
        $service->getUserStatistics($uid);
        $service->getCustomersWithInvoiceTotals($uid);

        $expectedKeys = [
            'analytics:user:' . $user->id . ':stats',
            'analytics:user:' . $user->id . ':customers',
        ];

        foreach ($expectedKeys as $key) {
            $taggedCache = Cache::tags(['analytics.user.' . $user->id]);
            $this->assertNotNull(
                $taggedCache->get($key),
            );
        }

        // Second calls (should hit cache). We re-fetch and ensure values identical.
        $stats1 = $service->getUserStatistics($uid);
        $stats2 = $service->getUserStatistics($uid);
        $this->assertEquals(
            $stats1,
            $stats2,
            'User statistics should be consistent between cached calls.',
        );

    }
}
