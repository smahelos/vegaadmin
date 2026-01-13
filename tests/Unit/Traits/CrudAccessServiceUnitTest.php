<?php

namespace Tests\Unit\Traits;

use App\Infrastructure\Authorization\Services\CrudAccessService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CrudAccessServiceUnitTest extends TestCase
{
    #[Test]
    public function service_has_configure_method_with_signature(): void
    {
        $reflection = new \ReflectionClass(CrudAccessService::class);
        $this->assertTrue($reflection->hasMethod('configureCrudAccess'));
        $method = $reflection->getMethod('configureCrudAccess');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('crud', $params[0]->getName());
        $this->assertEquals('userId', $params[1]->getName());
        $this->assertEquals('int', $params[1]->getType()->getName());
    }
}
