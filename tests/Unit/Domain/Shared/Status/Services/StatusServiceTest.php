<?php

namespace Tests\Unit\Domain\Shared\Status\Services;

use App\Domain\Shared\Status\Contracts\StatusServiceInterface;
use App\Domain\Shared\Status\Services\StatusService;
use ReflectionClass;
use Tests\TestCase;

class StatusServiceTest extends TestCase
{
    public function test_service_contract_and_public_methods(): void
    {
        $service = app(StatusServiceInterface::class);
        $this->assertInstanceOf(StatusService::class, $service);

        $ref = new ReflectionClass($service);
        $methods = collect($ref->getMethods())->filter(fn ($m) => $m->isPublic() && $m->class === StatusService::class)->map->getName()->values()->all();
        sort($methods);
        $this->assertSame([
            '__construct',
            'clearStatusCaches',
            'getAllCategories',
            'getIdToSlugMap',
            'getSlugToIdMap',
            'translateSlug',
        ], $methods);
    }
}
