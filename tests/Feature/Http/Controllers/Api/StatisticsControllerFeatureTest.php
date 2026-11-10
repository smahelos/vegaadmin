<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Enums\Permission;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\Analytics\FakeApiStatisticsRepository;
use Tests\Support\Fakes\Shared\FixedClock;
use Tests\TestCase;

final class StatisticsControllerFeatureTest extends TestCase
{
    private FakeApiStatisticsRepository $statistics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statistics = new FakeApiStatisticsRepository;
        $this->app->instance(ApiStatisticsRepository::class, $this->statistics);
        $this->app->instance(
            ClockInterface::class,
            new FixedClock(
                new DateTimeImmutable('2026-10-24 12:00:00'),
            ),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function routes(): array
    {
        return [
            'monthly revenue' => ['api.statistics.monthly-revenue'],
            'client revenue' => ['api.statistics.customer-revenue'],
            'invoice status' => ['api.statistics.invoice-status'],
            'payment methods' => ['api.statistics.payment-methods'],
            'revenue and expenses' => ['api.statistics.revenue-expenses'],
        ];
    }

    #[Test]
    #[DataProvider('routes')]
    public function guest_gets_401(string $route): void
    {
        $httpResponse = $this->getJson(
            route($route),
        );
        $httpResponse->assertUnauthorized();

        $this->assertSame([], $this->statistics->calls);
    }

    #[Test]
    #[DataProvider('routes')]
    public function user_without_statistics_permission_gets_403(string $route): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo(Permission::API_ACCESS);

        $httpClient = $this->actingAs($user, 'web');
        $httpResponse = $httpClient->getJson(
            route($route),
        );
        $httpResponse->assertForbidden();

        $this->assertSame([], $this->statistics->calls);
    }

    #[Test]
    #[DataProvider('routes')]
    public function queries_only_the_statistics_of_the_signed_in_user(string $route): void
    {
        $user = $this->statisticsUser();

        $httpClient = $this->actingAs($user, 'web');
        $httpResponse = $httpClient->getJson(
            route($route),
        );
        $httpResponse->assertOk();

        $this->assertNotEmpty($this->statistics->calls);
        foreach ($this->statistics->calls as $call) {
            $this->assertSame($user->id, $call['userId']->toInt());
        }
    }

    #[Test]
    public function a_browser_session_signs_in_requests_from_a_stateful_domain(): void
    {
        config(['sanctum.stateful' => ['billex.test']]);

        $this->withCredentials();
        $this->withCookie(
            config('session.cookie'),
            $this->storedSessionOf($this->statisticsUser()),
        );
        $this->withHeader('Referer', 'https://billex.test/cs/dashboard');
        $response = $this->getJson(
            route('api.statistics.monthly-revenue'),
        );
        $response->assertOk();
    }

    #[Test]
    public function a_browser_session_is_ignored_for_requests_from_another_domain(): void
    {
        config(['sanctum.stateful' => ['billex.test']]);

        $this->withCredentials();
        $this->withCookie(
            config('session.cookie'),
            $this->storedSessionOf($this->statisticsUser()),
        );
        $this->withHeader('Referer', 'https://elsewhere.test/');
        $response = $this->getJson(
            route('api.statistics.monthly-revenue'),
        );
        $response->assertUnauthorized();
    }

    #[Test]
    public function defaults_to_the_last_year_in_whole_months(): void
    {
        $httpClient = $this->actingAs($this->statisticsUser(), 'web');
        $httpResponse = $httpClient->getJson(
            route('api.statistics.monthly-revenue'),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertJsonPath('data.0.month', '2026-01');

        $call = $this->statistics->calls[0];
        $this->assertSame(
            '2025-10-01',
            $call['from']->format('Y-m-d'),
        );
        $this->assertNull($call['to']);
        $this->assertSame([], $call['contactIds']);
    }

    #[Test]
    public function passes_the_period_dates_and_contacts_to_the_query(): void
    {
        $query = http_build_query(
            [
                'time_range' => 'half_year',
                'date_to' => '2026-09-30',
                'contacts' => [3, 7],
            ],
        );

        $httpClient = $this->actingAs($this->statisticsUser(), 'web');
        $httpResponse = $httpClient->getJson(
            route('api.statistics.invoice-status') . '?' . $query,
        );
        $httpResponse->assertOk();

        $call = $this->statistics->calls[0];
        $this->assertSame(
            '2026-04-01',
            $call['from']->format('Y-m-d'),
        );
        $this->assertSame(
            '2026-09-30',
            $call['to']?->format('Y-m-d'),
        );
        $this->assertSame(
            [3, 7],
            array_map(static fn ($id): int => $id->value, $call['contactIds']),
        );
    }

    #[Test]
    public function an_explicit_start_date_wins_over_the_period(): void
    {
        $httpClient = $this->actingAs($this->statisticsUser(), 'web');
        $httpResponse = $httpClient->getJson(
            route('api.statistics.customer-revenue', [
                'time_range' => 'month',
                'date_from' => '2026-01-15',
            ]),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertJsonPath('data.0.customer_name', 'Acme');

        $this->assertSame(
            '2026-01-15',
            $this->statistics->calls[0]['from']->format('Y-m-d'),
        );
    }

    #[Test]
    public function merges_revenue_and_expenses_ordered_by_month(): void
    {
        $httpClient = $this->actingAs($this->statisticsUser(), 'web');
        $httpResponse = $httpClient->getJson(
            route('api.statistics.revenue-expenses'),
        );
        $response = $httpResponse->assertOk();

        $this->assertSame(
            [
                ['2026-01', 'revenue'],
                ['2026-01', 'expense'],
                ['2026-02', 'revenue'],
            ],
            array_map(
                static fn (array $row): array => [$row['month'], $row['type']],
                $response->json('data'),
            ),
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidFilters(): array
    {
        return [
            'unknown period' => [['time_range' => '6month'], 'time_range'],
            'malformed date' => [['date_from' => '24.10.2026'], 'date_from'],
            'end before start' => [['date_from' => '2026-05-01', 'date_to' => '2026-04-30'], 'date_to'],
            'client id not a number' => [['contacts' => ['abc']], 'contacts.0'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    #[Test]
    #[DataProvider('invalidFilters')]
    public function rejects_an_invalid_filter(array $filter, string $field): void
    {
        $httpClient = $this->actingAs($this->statisticsUser(), 'web');
        $httpResponse = $httpClient->getJson(
            route('api.statistics.monthly-revenue', $filter),
        );
        $httpResponse = $httpResponse->assertUnprocessable();
        $httpResponse->assertJsonValidationErrors($field);

        $this->assertSame([], $this->statistics->calls);
    }

    /**
     * A web session of the user as the browser holds it: stored by the session handler, sent as a cookie.
     */
    private function storedSessionOf(User $user): string
    {
        $session = $this->app['session']->driver();
        $session->setId(
            Str::random(40),
        );
        $guard = Auth::guard('web');
        $session->put(
            $guard->getName(),
            $user->id,
        );
        $session->save();
        $session->flush();

        return $session->getId();
    }

    private function statisticsUser(): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole('frontend_user');

        return $user;
    }
}
