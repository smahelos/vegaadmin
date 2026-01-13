<?php

namespace Tests\Unit\Domain\Shared\Status\Services;

use Tests\TestCase;
use ReflectionClass;
use App\Domain\Shared\Status\Services\StatusService;
use App\Domain\Shared\Status\Contracts\StatusServiceInterface;

class StatusServiceTest extends TestCase
{
    public function test_service_contract_and_public_methods(): void
    {
        $service = app(StatusServiceInterface::class);
        $this->assertInstanceOf(StatusService::class, $service);

        $ref = new ReflectionClass($service);
        $methods = collect($ref->getMethods())->filter(fn($m)=>$m->isPublic() && $m->class === StatusService::class)->map->getName()->values()->all();
        sort($methods);
        $this->assertSame(['__construct','clearStatusCaches','getAllCategories','getIdToSlugMap','getSlugToIdMap','translateSlug'], $methods);
    }
}
