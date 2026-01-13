<?php

namespace Tests\Unit\Domain\Content\Contracts;

use App\Domain\Content\Contracts\PageServiceInterface;
use App\Domain\Content\Services\PageService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageServiceInterfaceTest extends TestCase
{
    private \ReflectionClass $interface;
    private \ReflectionClass $implementation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interface = new \ReflectionClass(PageServiceInterface::class);
        $this->implementation = new \ReflectionClass(PageService::class);
    }

    #[Test]
    public function interface_methods_have_return_types(): void
    {
        foreach ($this->interface->getMethods() as $method) {
            $this->assertNotNull($method->getReturnType(), 'Missing return type on interface method ' . $method->getName());
        }
    }

    #[Test]
    public function implementation_matches_interface_signatures(): void
    {
        foreach ($this->interface->getMethods() as $imethod) {
            $this->assertTrue($this->implementation->hasMethod($imethod->getName()), 'Missing method ' . $imethod->getName() . ' in implementation');
            $cmethod = $this->implementation->getMethod($imethod->getName());
            $this->assertEquals((string)$imethod->getReturnType(), (string)$cmethod->getReturnType(), 'Return type mismatch for ' . $imethod->getName());
            $iparams = $imethod->getParameters();
            $cparams = $cmethod->getParameters();
            $this->assertCount(count($iparams), $cparams, 'Parameter count mismatch for ' . $imethod->getName());
            foreach ($iparams as $idx => $iparam) {
                $this->assertEquals($iparam->getName(), $cparams[$idx]->getName(), 'Param name mismatch #' . $idx . ' for ' . $imethod->getName());
                $this->assertEquals((string)$iparam->getType(), (string)$cparams[$idx]->getType(), 'Param type mismatch #' . $idx . ' for ' . $imethod->getName());
            }
        }
    }
}
