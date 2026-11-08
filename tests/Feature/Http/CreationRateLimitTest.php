<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class CreationRateLimitTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        Config::set('billex.rate_limits.create', 1);
        Config::set('billex.rate_limits.guest_invoice', 1);
    }

    #[Test]
    public function a_user_creating_faster_than_the_limit_is_refused(): void
    {
        $this->actingAs($this->regularUser);

        $first = $this->post('/cs/product');
        $second = $this->post('/cs/product');

        $this->assertNotSame(429, $first->getStatusCode());
        $second->assertStatus(429);
    }

    #[Test]
    public function the_limit_counts_every_creation_path_of_the_user(): void
    {
        $this->actingAs($this->regularUser);

        $this->post('/cs/product');
        $response = $this->post('/cs/invoice');

        $response->assertStatus(429);
    }

    #[Test]
    public function guest_invoices_are_limited_per_ip_address(): void
    {
        $first = $this->post('/cs/guest-invoices');
        $second = $this->post('/cs/guest-invoices');
        $otherAddress = $this->post(
            '/cs/guest-invoices',
            [],
            ['REMOTE_ADDR' => '10.0.0.2'],
        );

        $this->assertNotSame(429, $first->getStatusCode());
        $second->assertStatus(429);
        $this->assertNotSame(429, $otherAddress->getStatusCode());
    }

    #[Test]
    public function an_admin_creating_faster_than_the_limit_is_refused(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $first = $this->post('/admin/product');
        $second = $this->post('/admin/product');

        $this->assertNotSame(429, $first->getStatusCode());
        $second->assertStatus(429);
    }
}
