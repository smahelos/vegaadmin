<?php

namespace Tests\Support\Traits;

use Illuminate\Support\Facades\Config;

/**
 * Trait to rebind CurrencyExchangeServiceInterface to deterministic stub for stable feature tests.
 */
trait UsesDeterministicExchange
{
    protected function setUpDeterministicExchange(): void
    {
        // Enable deterministic mode via config so provider selects deterministic service.
        Config::set('exchange.deterministic_enabled', true);
    }
}
