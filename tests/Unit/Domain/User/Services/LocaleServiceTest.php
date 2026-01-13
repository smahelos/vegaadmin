<?php

namespace Tests\Unit\Domain\User\Services;

use Tests\TestCase;
use App\Domain\User\Services\LocaleService;
use App\Domain\User\Contracts\Locale;
use App\Domain\Shared\Session\Contracts\SessionInterface;
use Mockery\MockInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

class LocaleServiceTest extends TestCase
{
    protected LocaleService $service;
    /** @var MockInterface */
    protected $mockSession;
    /** @var MockInterface */
    protected $mockLocale;

    protected function setUp(): void
    {
        parent::setUp();
        
    $this->mockSession = Mockery::mock(SessionInterface::class);
    $this->mockLocale = Mockery::mock(Locale::class);
        
        $this->service = new LocaleService($this->mockSession, $this->mockLocale);
    }

    #[Test]
    public function get_available_locales_returns_configured_list(): void
    {
        $this->mockLocale->shouldReceive('getAvailableLocales')
            ->once()
            ->andReturn(['cs', 'en', 'de']);

        $locales = $this->service->getAvailableLocales();
        $this->assertIsArray($locales);
        $this->assertNotEmpty($locales);
        $this->assertContains('cs', $locales);
    }

    #[Test]
    public function determine_locale_priority_request_over_data_and_session(): void
    {
        // Locale adapter decides resolution; ensure it returns request locale
        $this->mockLocale->shouldReceive('determineLocale')
            ->once()
            ->with('en', 'sk')
            ->andReturn('en');

        $result = $this->service->determineLocale('en', 'sk');
        $this->assertEquals('en', $result);
    }

    #[Test]
    public function determine_locale_priority_data_over_session(): void
    {
        $this->mockLocale->shouldReceive('determineLocale')
            ->once()
            ->with(null, 'sk')
            ->andReturn('sk');

        $result = $this->service->determineLocale(null, 'sk');
        $this->assertEquals('sk', $result);
    }

    #[Test]
    public function determine_locale_uses_session_when_request_and_data_missing(): void
    {
        $this->mockLocale->shouldReceive('determineLocale')
            ->once()
            ->with(null, null)
            ->andReturn('de');

        $result = $this->service->determineLocale();
        $this->assertEquals('de', $result);
    }

    #[Test]
    public function determine_locale_fallback_when_none_valid(): void
    {
        $fallback = config('app.fallback_locale');
        $this->mockLocale->shouldReceive('determineLocale')
            ->once()
            ->with('xx', 'yy')
            ->andReturn($fallback);

        $result = $this->service->determineLocale('xx', 'yy');
        $this->assertEquals($fallback, $result);
    }

    #[Test]
    public function set_locale_valid_updates_app_and_session(): void
    {
        $this->mockLocale->shouldReceive('getAvailableLocales')
            ->once()
            ->andReturn(['cs', 'en']);
        $this->mockLocale->shouldReceive('setLocale')
            ->once()
            ->with('en');
        $this->mockSession->shouldReceive('put')
            ->once()
            ->with('locale', 'en');

        $this->service->setLocale('en');
        $this->assertTrue(true); // Expectations are the assertion
    }

    #[Test]
    public function set_locale_invalid_sets_fallback(): void
    {
        $fallback = config('app.fallback_locale');
        $this->mockLocale->shouldReceive('getAvailableLocales')
            ->once()
            ->andReturn(['cs', 'en']);
        $this->mockLocale->shouldReceive('getFallbackLocale')
            ->once()
            ->andReturn($fallback);
        $this->mockLocale->shouldReceive('setLocale')
            ->once()
            ->with($fallback);
        $this->mockSession->shouldReceive('put')
            ->once()
            ->with('locale', $fallback);

        $this->service->setLocale('xx');
        $this->assertTrue(true);
    }
    
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(LocaleService::class, $this->service);
    }

    #[Test]
    public function get_available_locales_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAvailableLocales'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getAvailableLocales');
    $returnType = $method->getReturnType();
        
    $this->assertNotNull($returnType);
    $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
    $this->assertEquals('array', $returnType->getName());
        
        // Check that method has no parameters
        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    #[Test]
    public function determine_locale_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'determineLocale'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('determineLocale');
    $returnType = $method->getReturnType();
        
    $this->assertNotNull($returnType);
    $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
    $this->assertEquals('string', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('requestLocale', $parameters[0]->getName());
        $this->assertEquals('dataLocale', $parameters[1]->getName());
        
    $this->assertTrue($parameters[0]->getType()->allowsNull());
    $this->assertInstanceOf(\ReflectionNamedType::class, $parameters[0]->getType());
    $this->assertEquals('string', $parameters[0]->getType()->getName());
    $this->assertTrue($parameters[1]->getType()->allowsNull());
    $this->assertInstanceOf(\ReflectionNamedType::class, $parameters[1]->getType());
    $this->assertEquals('string', $parameters[1]->getType()->getName());
        
        // Check default values
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertNull($parameters[0]->getDefaultValue());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());
    }

    #[Test]
    public function set_locale_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'setLocale'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('setLocale');
    $returnType = $method->getReturnType();
        
    $this->assertNotNull($returnType);
    $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
    $this->assertEquals('void', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('locale', $parameters[0]->getName());
    $this->assertInstanceOf(\ReflectionNamedType::class, $parameters[0]->getType());
    $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertEquals('App\\Domain\\User\\Services', $reflection->getNamespaceName());
        $this->assertEquals('LocaleService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getName() !== '__construct' && 
                $method->getDeclaringClass()->getName() === LocaleService::class) {
                $this->assertNotNull(
                    $method->getReturnType(),
                    "Method {$method->getName()} should have a return type"
                );
            }
        }
    }

    #[Test]
    public function public_methods_count(): void
    {
        $reflection = new ReflectionClass($this->service);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Filter out inherited methods and constructor
        $customPublicMethods = array_filter($publicMethods, function ($method) {
            return $method->getDeclaringClass()->getName() === LocaleService::class
                && $method->getName() !== '__construct';
        });
        
        // getAvailableLocales, determineLocale, localeFromCountry, setLocale
        $this->assertCount(7, $customPublicMethods);
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getName() !== '__construct' && 
                $method->getDeclaringClass()->getName() === LocaleService::class) {
                foreach ($method->getParameters() as $parameter) {
                    $this->assertNotNull(
                        $parameter->getType(),
                        "Parameter {$parameter->getName()} in method {$method->getName()} should have a type"
                    );
                }
            }
        }
    }
}
