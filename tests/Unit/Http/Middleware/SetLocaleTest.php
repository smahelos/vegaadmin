<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    private SetLocale $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SetLocale();
        config(['app.available_locales' => ['cs','en','de','sk']]);
        config(['app.locale' => 'cs']);
        config(['app.fallback_locale' => 'cs']);
    }

    #[Test]
    public function handle_method_signature_is_correct(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $this->assertTrue($reflection->hasMethod('handle'));
        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('request', $params[0]->getName());
        $this->assertEquals('next', $params[1]->getName());
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('Symfony\\Component\\HttpFoundation\\Response', $returnType->getName());
        } else {
            $this->fail('Return type is not a named type');
        }
    }

    #[Test]
    public function unsupported_segment_results_in_fallback_locale(): void
    {
        $request = Request::create('/xx/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('cs', app()->getLocale());
    }

    #[Test]
    public function supported_segment_sets_locale(): void
    {
        $request = Request::create('/en/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('en', app()->getLocale());
    }
}
