<?php

namespace Tests\Feature\Domain\Payment\Services;

use App\Domain\Payment\Contracts\GatewayRegistryInterface;
use App\Domain\Payment\Services\GatewayRegistry;
use Tests\Support\Fakes\Payment\FakePaymentGateway;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;
use PHPUnit\Framework\Attributes\Test;

class GatewayRegistryTest extends TestCase
{
    use RefreshDatabaseWithData;

    private GatewayRegistryInterface $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new GatewayRegistry();
    }

    #[Test]
    public function register_and_retrieve_gateway(): void
    {
        $fake = new FakePaymentGateway();
        $this->registry->register('fake', $fake);
        $this->assertSame($fake, $this->registry->get('fake'));
        $this->assertContains('fake', $this->registry->names());
    }

    #[Test]
    public function remove_gateway(): void
    {
        $fake = new FakePaymentGateway();
        $this->registry->register('fake', $fake);
        $this->registry->remove('fake');
        $this->assertNull($this->registry->get('fake'));
        $this->assertNotContains('fake', $this->registry->names());
    }

    #[Test]
    public function metadata_is_populated_on_register(): void
    {
        $fake = new FakePaymentGateway();
        $this->registry->register('fake', $fake);
        $meta = $this->registry->metadata('fake');
        $this->assertNotNull($meta);
        $this->assertEquals('Fake Gateway', $meta['display_name']);
        $this->assertEquals(['EUR','USD'], $meta['currencies']);
        $all = $this->registry->allMetadata();
        $this->assertArrayHasKey('fake', $all);
    }
}
