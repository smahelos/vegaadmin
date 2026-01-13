<?php

namespace Tests\Feature\Domain\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\CurrencyServiceInterface;
use App\Domain\Shared\Http\Contracts\HttpClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        try { while (DB::transactionLevel() > 0) { DB::rollBack(); } } catch (\Exception $e) { DB::disconnect(); }
        parent::tearDown();
    }

    #[Test]
    public function service_instantiated_correctly(): void 
    { $this->assertInstanceOf(CurrencyServiceInterface::class, $this->service); }

    #[Test]
    public function get_all_currencies_returns_api_data(): void
    {
        $client = new class implements HttpClientInterface {
            public function get(string $url, array $headers = []): array {
                return [
                    'success' => true,
                    'status' => 200,
                    'data' => ['rates' => ['USD'=>1,'EUR'=>0.85,'CZK'=>21.5,'GBP'=>0.73,'PLN'=>3.8]]
                ];
            }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $result = $this->service->getAllCurrencies();
        foreach (['USD','EUR','CZK','GBP','PLN'] as $code) { $this->assertArrayHasKey($code,$result); $this->assertEquals($code,$result[$code]); }
    }

    #[Test]
    public function get_all_currencies_returns_sorted_data(): void
    {
        $client = new class implements HttpClientInterface {
            public function get(string $url, array $headers = []): array {
                return [ 'success'=>true, 'status'=>200, 'data'=>['rates'=>['ZAR'=>15,'USD'=>1,'EUR'=>0.85,'AUD'=>1.35]] ];
            }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $this->assertEquals(['AUD','EUR','USD','ZAR'], array_keys($this->service->getAllCurrencies()));
    }

    #[Test]
    public function get_all_currencies_returns_fallback_when_api_fails(): void
    {
        $client = new class implements HttpClientInterface {
            public function get(string $url, array $headers = []): array { return ['success'=>false,'status'=>500,'data'=>[]]; }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $result = $this->service->getAllCurrencies();
        foreach (['CZK','EUR','USD','GBP'] as $code) { $this->assertArrayHasKey($code,$result); }
    }

    #[Test]
    public function get_all_currencies_handles_malformed_api_response(): void
    {
        $client = new class implements HttpClientInterface {
            public function get(string $url, array $headers = []): array { return ['success'=>true,'status'=>200,'data'=>['error'=>'x']]; }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $result = $this->service->getAllCurrencies();
        $this->assertArrayHasKey('CZK',$result);
    }

    #[Test]
    public function get_all_currencies_caches_result(): void
    {
        $client = new class implements HttpClientInterface {
            public int $calls = 0;
            public function get(string $url, array $headers = []): array { $this->calls++; return ['success'=>true,'status'=>200,'data'=>['rates'=>['USD'=>1,'EUR'=>0.85,'CZK'=>21.5]]]; }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $r1 = $this->service->getAllCurrencies(); $r2 = $this->service->getAllCurrencies();
        $this->assertEquals($r1,$r2);
        $this->assertEquals(1, $client->calls);
    }

    #[Test]
    public function get_all_currencies_logs_errors(): void
    {
        Log::spy();
        $client = new class implements HttpClientInterface {
            public function get(string $url, array $headers = []): array { throw new \Exception('timeout'); }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $this->service->getAllCurrencies();
        // Our domain logger adapter delegates to Log::log('warning', ...), not Log::warning(...)
        Log::shouldHaveReceived('log');
    }

    #[Test]
    public function get_common_currencies_returns_subset(): void
    {
        $client = new class implements HttpClientInterface {
            public function get(string $url, array $headers = []): array {
                return ['success'=>true,'status'=>200,'data'=>['rates'=>[
                    'USD'=>1,'EUR'=>0.85,'CZK'=>21.5,'GBP'=>0.73,'PLN'=>3.8,'HUF'=>300,'CHF'=>0.92,'JPY'=>110
                ]]];
            }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $subset = $this->service->getCommonCurrencies();
        foreach (['CZK','EUR','USD','GBP','PLN','HUF','CHF'] as $c) { $this->assertArrayHasKey($c,$subset); }
        $this->assertArrayNotHasKey('JPY',$subset);
    }

    #[Test]
    public function get_common_currencies_handles_missing(): void
    {
        $client = new class implements HttpClientInterface {
            public function get(string $url, array $headers = []): array { return ['success'=>true,'status'=>200,'data'=>['rates'=>['USD'=>1,'EUR'=>0.85,'CZK'=>21.5]]]; }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $res = $this->service->getCommonCurrencies();
        foreach (['USD','EUR','CZK'] as $c) { $this->assertArrayHasKey($c,$res); }
        foreach (['GBP','PLN','HUF','CHF'] as $c) { $this->assertArrayNotHasKey($c,$res); }
    }

    #[Test]
    public function get_common_currencies_caches_result(): void
    {
        $client = new class implements HttpClientInterface {
            public int $calls = 0;
            public function get(string $url, array $headers = []): array { $this->calls++; return ['success'=>true,'status'=>200,'data'=>['rates'=>['USD'=>1,'EUR'=>0.85,'CZK'=>21.5,'GBP'=>0.73]]]; }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $a=$this->service->getCommonCurrencies(); $b=$this->service->getCommonCurrencies(); $this->assertEquals($a,$b);
        $this->assertEquals(1, $client->calls);
    }

    #[Test]
    public function methods_handle_api_timeout(): void
    {
        $client = new class implements HttpClientInterface {
            public function get(string $url, array $headers = []): array { throw new \Exception('timeout'); }
            public function post(string $url, array $data = [], array $headers = []): array { return []; }
            public function put(string $url, array $data = [], array $headers = []): ?array { return ['success'=>false,'status'=>500,'data'=>[]]; }
        };
        $this->app->instance(HttpClientInterface::class, $client);
        Cache::flush();
        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $all=$this->service->getAllCurrencies(); $this->assertNotEmpty($all);
        $common=$this->service->getCommonCurrencies(); foreach ($common as $code=>$v){ $this->assertArrayHasKey($code,$all); }
    }
}
