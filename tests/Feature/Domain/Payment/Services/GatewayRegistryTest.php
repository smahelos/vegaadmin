<?php

namespace Tests\Feature\Domain\Payment\Services;

use App\Domain\Payment\Contracts\GatewayRegistryInterface;
use App\Domain\Payment\Services\GatewayRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\Payment\FakePaymentGateway;
use Tests\TestCase;

class GatewayRegistryTest extends TestCase
{
    private GatewayRegistryInterface $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new GatewayRegistry;
    }

    #[Test]
    public function register_and_retrieve_gateway(): void
    {
        $fake = new FakePaymentGateway;
        $this->registry->register('fake', $fake);
        $this->assertSame($fake, $this->registry->get('fake'));
        $this->assertContains('fake', $this->registry->names());
    }
}
