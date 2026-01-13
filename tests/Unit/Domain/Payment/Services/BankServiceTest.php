<?php

namespace Tests\Unit\Domain\Payment\Services;

use App\Domain\Payment\Contracts\BankDtoRepository;
use App\Domain\Payment\Services\BankService;
use App\Domain\Payment\Contracts\BankServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BankServiceTest extends TestCase
{
    private BankService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BankService(
            // dependencies if any would go here
            $this->createMock(BankDtoRepository::class)
        );
    }

    #[Test]
    public function service_and_interface(): void
    {
        $this->assertInstanceOf(BankService::class, $this->service);
        $this->assertInstanceOf(BankServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_banks_for_dropdown_signature(): void
    {
        $r = new \ReflectionClass($this->service);
        $m = $r->getMethod('getBanksForDropdown');
        $this->assertTrue($m->isPublic());
        $params = $m->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('country', $params[0]->getName());
        $this->assertEquals('string', (string)$params[0]->getType());
        $this->assertTrue($params[0]->isDefaultValueAvailable());
        $this->assertEquals('CZ', $params[0]->getDefaultValue());
        $this->assertEquals('array', (string)$m->getReturnType());
    }

    #[Test]
    public function get_banks_for_js_signature(): void
    {
        $r = new \ReflectionClass($this->service);
        $m = $r->getMethod('getBanksForJs');
        $this->assertTrue($m->isPublic());
        $params = $m->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('country', $params[0]->getName());
        $this->assertEquals('string', (string)$params[0]->getType());
        $this->assertTrue($params[0]->isDefaultValueAvailable());
        $this->assertEquals('CZ', $params[0]->getDefaultValue());
        $this->assertEquals('array', (string)$m->getReturnType());
    }

    #[Test]
    public function class_structure(): void
    {
        $r = new \ReflectionClass($this->service);
        $this->assertTrue($r->isInstantiable());
        $this->assertEquals('App\\Domain\\Payment\\Services', $r->getNamespaceName());
    }
}
