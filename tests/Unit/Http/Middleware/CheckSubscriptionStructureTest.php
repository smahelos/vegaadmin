<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\CheckSubscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckSubscriptionStructureTest extends TestCase
{
    private CheckSubscription $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckSubscription();
    }

    #[Test]
    public function handle_method_is_public(): void
    {
        $ref = new \ReflectionClass($this->middleware);
        $method = $ref->getMethod('handle');
        $this->assertTrue($method->isPublic());
    }
}
