<?php

namespace Tests\Unit\Traits;

use App\Infrastructure\Authorization\Adapters\User\SpatieAuthorizationAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SpatieAuthorizationAdapterUnitTest extends TestCase
{
    #[Test]
    public function adapter_has_expected_methods(): void
    {
        $reflection = new \ReflectionClass(SpatieAuthorizationAdapter::class);
        $expected = [
            'isWebAdmin',
            'isBackpackAdmin',
            'hasBackpackPermission',
            'hasBackpackViewPermission',
            'hasCreateEditPermission',
            'hasFrontendUserRole',
            'canAccessAnyClients',
            'canAccessAnySuppliers',
        ];
        foreach ($expected as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing method: {$method}");
        }
    }
}
