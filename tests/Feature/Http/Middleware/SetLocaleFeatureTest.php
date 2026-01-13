<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetLocaleFeatureTest extends TestCase
{
    use RefreshDatabase;

    private SetLocale $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SetLocale();
        config(['app.available_locales' => ['cs','en','de','sk']]);
        config(['app.locale' => 'cs']);
    }

    #[Test]
    public function locale_from_first_url_segment_takes_priority(): void
    {
        $request = Request::create('/en/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('en', app()->getLocale());
    }

    #[Test]
    public function unsupported_segment_falls_back_to_default(): void
    {
        $request = Request::create('/xx/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('cs', app()->getLocale());
    }

    #[Test]
    public function session_locale_used_when_no_segment(): void
    {
        session(['locale' => 'de']);
        $request = Request::create('/dashboard');
        $this->middleware->handle($request, fn() => response('OK'));
        $this->assertEquals('de', app()->getLocale());
    }
}
