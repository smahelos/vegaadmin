<?php

namespace Tests\Unit\Application\Invoice\Services;

use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemporaryInvoiceStoreTest extends TestCase
{
    #[Test]
    public function it_stores_and_retrieves_invoice_data(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $data = ['invoice_vs' => 'INV-UNIT', 'lang' => 'en', 'amount' => 123.45];

        $token = $store->store($data, minutes: 5);

        $this->assertIsString($token);
        $this->assertTrue(strlen($token) >= 16, 'Token length expectation');

        $retrieved = $store->get($token);
        $this->assertNotNull($retrieved);
        $this->assertEquals($data['invoice_vs'], $retrieved['invoice_vs']);
        $this->assertEquals($data['lang'], $retrieved['lang']);
    }

    #[Test]
    public function it_returns_null_for_unknown_token(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $this->assertNull($store->get('nonexistent-token'));
    }

    #[Test]
    public function it_deletes_token(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $data = ['invoice_vs' => 'INV-DEL', 'lang' => 'cs'];
        $token = $store->store($data, minutes: 5);

        $this->assertNotNull($store->get($token));
        $this->assertTrue($store->delete($token));
        $this->assertNull($store->get($token));
    }
}
