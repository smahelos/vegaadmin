<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Models\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DatabaseTest extends TestCase
{
    #[Test]
    public function stores_a_utc_time_that_central_european_summer_time_skips(): void
    {
        $subscriptionFactory = Subscription::factory();
        $subscription = $subscriptionFactory->create(
            ['starts_at' => '2027-03-28 02:26:41'],
        );

        $stored = $subscription->fresh();
        $this->assertSame(
            '2027-03-28 02:26:41',
            $stored->starts_at->format('Y-m-d H:i:s'),
        );
    }
}
