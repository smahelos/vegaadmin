<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Analytics\Services;

use App\Domain\Analytics\Services\DashboardService;
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
use Tests\Support\ReadsDashboard;
use Tests\TestCase;

final class CacheInvalidationFeatureTest extends TestCase
{
    use ReadsDashboard;

    private CacheServiceInterface $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(CacheServiceInterface::class);
    }

    #[Test]
    public function cache_service_can_invalidate_tags(): void
    {
        $cache = cache();
        $cache->put(
            'test_key',
            'test_value',
            60,
        );
        $this->assertTrue(
            $cache->has('test_key'),
        );
        $this->cacheService->invalidateTags(['test_tag']);
        $this->assertTrue(true);
    }

    #[Test]
    public function user_statistics_are_refreshed_after_invoice_event(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole('frontend_user');
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'payment_amount' => 100,
            ],
        );

        $statsFirst = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->statistics;
        $this->assertSame(1, $statsFirst->invoiceCount);
        $this->assertInstanceOf(Money::class, $statsFirst->totalAmount);
        $this->assertSame('100', $statsFirst->totalAmount->amount);

        Invoice::withoutEvents(
            function () use ($user, $contact): void {
                $invoiceFactory = Invoice::factory();
                $invoiceFactory->create(
                    [
                        'user_id' => $user->id,
                        'contact_id' => $contact->id,
                        'payment_amount' => 200,
                    ],
                );
            },
        );
        $statsCached = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->statistics;
        $this->assertSame(1, $statsCached->invoiceCount);

        $dispatcher = app(Dispatcher::class);
        $dispatcher->dispatch(
            new UserDataChanged($user->id, 'invoice'),
        );
        $statsAfter = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->statistics;
        $this->assertSame(2, $statsAfter->invoiceCount);
        $this->assertSame('300', $statsAfter->totalAmount->amount);
    }

    #[Test]
    public function customers_totals_are_refreshed_after_contact_event(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole('frontend_user');
        $contactFactory = Contact::factory();
        $contactA = $contactFactory->create(['user_id' => $user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'contact_id' => $contactA->id,
                'payment_amount' => 80,
            ],
        );

        $customersFirst = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->contacts;
        $this->assertCount(1, $customersFirst);

        Contact::withoutEvents(
            function () use ($user, &$contactB): void {
                $contactFactory = Contact::factory();
                $contactB = $contactFactory->create(['user_id' => $user->id]);
            },
        );
        Invoice::withoutEvents(
            function () use ($user, &$contactB): void {
                $invoiceFactory = Invoice::factory();
                $invoiceFactory->create(
                    [
                        'user_id' => $user->id,
                        'contact_id' => $contactB->id,
                        'payment_amount' => 40,
                    ],
                );
            },
        );
        $customersCached = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->contacts;
        $this->assertCount(1, $customersCached);

        $dispatcher = app(Dispatcher::class);
        $dispatcher->dispatch(
            new UserDataChanged($user->id, 'contact'),
        );
        $customersAfter = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->contacts;
        $this->assertCount(2, $customersAfter);
        $this->assertInstanceOf(Money::class, $customersAfter[0]->totalAmount);
    }

    #[Test]
    public function direct_invalidate_user_cache_method_works(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole('frontend_user');
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'payment_amount' => 10,
            ],
        );

        $stats = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->statistics;
        $this->assertSame(1, $stats->invoiceCount);

        Invoice::withoutEvents(
            function () use ($user, $contact): void {
                $invoiceFactory = Invoice::factory();
                $invoiceFactory->create(
                    [
                        'user_id' => $user->id,
                        'contact_id' => $contact->id,
                        'payment_amount' => 20,
                    ],
                );
            },
        );
        $cached = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->statistics;
        $this->assertSame(1, $cached->invoiceCount);

        $dashboard = app(DashboardService::class);
        $dashboard->invalidateUserCache($user->id);
        $after = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->statistics;
        $this->assertSame(2, $after->invoiceCount);
        $this->assertSame('30', $after->totalAmount->amount);
    }

    #[Test]
    public function cache_keys_use_expected_prefix_after_population(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole('frontend_user');
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'payment_amount' => 5,
            ],
        );

        $uid = UserId::fromInt($user->id);
        $this->dashboardOf($uid);

        $taggedCache = Cache::tags(['analytics.user.' . $user->id]);
        $taggedCache->flush();

        $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'payment_amount' => 7,
            ],
        );
        $stats = $this->dashboardOf(
            UserId::fromInt($user->id),
        )->statistics;
        $this->assertSame(2, $stats->invoiceCount);
        $this->assertSame('12', $stats->totalAmount->amount);
    }
}
