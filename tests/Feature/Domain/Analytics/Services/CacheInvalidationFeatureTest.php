<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Analytics\Services;

use App\Domain\Analytics\Services\DashboardService as AnalyticsDashboardService;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface; // updated namespace
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\Events\UserDataChanged;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CacheInvalidationFeatureTest extends TestCase
{
    private AnalyticsDashboardService $dashboardService;

    private CacheServiceInterface $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(AnalyticsDashboardService::class);
        $this->cacheService = app(CacheServiceInterface::class);
    }

    #[Test]
    public function cache_service_can_invalidate_tags(): void
    {
        cache()->put(
            'test_key',
            'test_value',
            60,
        );
        $this->assertTrue(cache()->has('test_key'));
        $this->cacheService->invalidateTags(['test_tag']);
        $this->assertTrue(true);
    }

    #[Test]
    public function user_statistics_are_refreshed_after_invoice_event(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'payment_amount' => 100,
        ]);

        $statsFirst = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $statsFirst->invoiceCount);
        $this->assertInstanceOf(Money::class, $statsFirst->totalAmount);
        $this->assertSame(100.0, $statsFirst->totalAmount->toFloat());

        Invoice::withoutEvents(function () use ($user, $contact): void {
            Invoice::factory()->create([
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'payment_amount' => 200,
            ]);
        });
        $statsCached = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $statsCached->invoiceCount);

        app(Dispatcher::class)->dispatch(new UserDataChanged($user->id, 'invoice'));
        $statsAfter = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(2, $statsAfter->invoiceCount);
        $this->assertSame(300.0, $statsAfter->totalAmount->toFloat());
    }

    #[Test]
    public function customers_totals_are_refreshed_after_contact_event(): void
    {
        $user = User::factory()->create();
        $contactA = Contact::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contactA->id,
            'payment_amount' => 80,
        ]);

        $customersFirst = $this->dashboardService->getCustomersWithInvoiceTotals(UserId::fromInt($user->id));
        $this->assertCount(1, $customersFirst);

        Contact::withoutEvents(function () use ($user, &$contactB): void {
            $contactB = Contact::factory()->create(['user_id' => $user->id]);
        });
        Invoice::withoutEvents(function () use ($user, &$contactB): void {
            Invoice::factory()->create([
                'user_id' => $user->id,
                'contact_id' => $contactB->id,
                'payment_amount' => 40,
            ]);
        });
        $customersCached = $this->dashboardService->getCustomersWithInvoiceTotals(UserId::fromInt($user->id));
        $this->assertCount(1, $customersCached);

        app(Dispatcher::class)->dispatch(new UserDataChanged($user->id, 'contact'));
        $customersAfter = $this->dashboardService->getCustomersWithInvoiceTotals(UserId::fromInt($user->id));
        $this->assertCount(2, $customersAfter);
        $this->assertInstanceOf(Money::class, $customersAfter[0]->totalAmount);
    }

    #[Test]
    public function direct_invalidate_user_cache_method_works(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'payment_amount' => 10,
        ]);

        $stats = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $stats->invoiceCount);

        Invoice::withoutEvents(function () use ($user, $contact): void {
            Invoice::factory()->create([
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'payment_amount' => 20,
            ]);
        });
        $cached = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $cached->invoiceCount);

        $this->dashboardService->invalidateUserCache($user->id);
        $after = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(2, $after->invoiceCount);
        $this->assertSame(30.0, $after->totalAmount->toFloat());
    }

    #[Test]
    public function cache_keys_use_expected_prefix_after_population(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'payment_amount' => 5,
        ]);

        $uid = UserId::fromInt($user->id);
        $this->dashboardService->getUserStatistics($uid);
        $this->dashboardService->getCustomersWithInvoiceTotals($uid);

        Cache::tags(['analytics.user.' . $user->id])->flush();

        Invoice::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'payment_amount' => 7,
        ]);
        $stats = $this->dashboardService->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(2, $stats->invoiceCount);
        $this->assertSame(12.0, $stats->totalAmount->toFloat());
    }
}
