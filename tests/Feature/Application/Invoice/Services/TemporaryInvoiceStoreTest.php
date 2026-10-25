<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice\Services;

use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TemporaryInvoiceStoreTest extends TestCase
{
    #[Test]
    public function stores_invoice_data_under_a_token_and_returns_it(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $data = [
            'invoice_vs' => 'INV-UNIT',
            'lang' => 'en',
            'amount' => 123.45,
        ];

        $token = $store->store($data, minutes: 5);

        $this->assertGreaterThanOrEqual(16, strlen($token));
        $retrieved = $store->get($token);
        $this->assertNotNull($retrieved);
        $this->assertSame('INV-UNIT', $retrieved['invoice_vs']);
        $this->assertSame('en', $retrieved['lang']);
    }

    #[Test]
    public function returns_null_for_an_unknown_token(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);

        $this->assertNull($store->get('nonexistent-token'));
    }

    #[Test]
    public function deleted_token_returns_nothing(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $token = $store->store(['invoice_vs' => 'INV-DEL', 'lang' => 'cs'], minutes: 5);

        $this->assertTrue($store->delete($token));

        $this->assertNull($store->get($token));
    }
}
