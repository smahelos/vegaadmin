<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PublicApiRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('billex.rate_limits.public_api', 1);
    }

    #[Test]
    public function the_public_api_is_limited_per_ip_address(): void
    {
        $first = $this->get('/api/countries');
        $second = $this->get('/api/countries');
        $otherAddress = $this->get(
            '/api/countries',
            ['REMOTE_ADDR' => '10.0.0.2'],
        );

        $first->assertOk();
        $second->assertStatus(429);
        $otherAddress->assertOk();
    }
}
