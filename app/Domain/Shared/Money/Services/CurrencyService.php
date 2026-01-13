<?php

namespace App\Domain\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\CurrencyServiceInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Http\Contracts\HttpClientInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;

class CurrencyService implements CurrencyServiceInterface
{
    /** API endpoint returning base currency and rates. */
    protected string $apiUrl = 'https://open.er-api.com/v6/latest';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheServiceInterface $cacheService,
        private readonly LogInterface $logger,
    )
    {}

    /** @inheritDoc */
    public function getAllCurrencies(): array
    {
        return $this->cacheService->remember('currencies_all', function () {
            try {
                $response = $this->httpClient->get($this->apiUrl);
                if ($response['success']) {
                    $data = $response['data'];
                    if (isset($data['rates']) && is_array($data['rates'])) {
                        $currencies = [];
                        foreach (array_keys($data['rates']) as $code) {
                            $code = strtoupper($code);
                            if (preg_match('/^[A-Z]{3}$/', $code)) {
                                $currencies[$code] = $code;
                            }
                        }
                        ksort($currencies);
                        return $currencies ?: $this->getFallbackCurrencies();
                    }
                }
                return $this->getFallbackCurrencies();
            } catch (\Exception $e) {
                $this->logger->log('warning', 'CurrencyService API failure: ' . $e->getMessage());
                return $this->getFallbackCurrencies();
            }
        }, 86400); // Cache for 24 hours
    }

    /** @inheritDoc */
    public function getCommonCurrencies(): array
    {
        return $this->cacheService->remember('currencies_common', function () {
            $commonCodes = ['CZK', 'EUR', 'USD', 'GBP', 'PLN', 'HUF', 'CHF'];
            $all = $this->getAllCurrencies();
            $result = [];
            foreach ($commonCodes as $code) {
                if (isset($all[$code])) { $result[$code] = $code; }
            }
            return $result ?: $this->getFallbackCurrencies();
        }, 86400); // Cache for 24 hours
    }

    /** Fallback minimal currency set. */
    private function getFallbackCurrencies(): array
    {
        return [ 'CZK' => 'CZK', 'EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP' ];
    }
}
