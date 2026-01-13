<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\InvoiceController;
use App\Application\Invoice\Contracts\InvoiceListingApplicationServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Structural unit tests for Api\\InvoiceController
 */
class InvoiceControllerTest extends TestCase
{
    #[Test]
    public function constructor_injects_invoice_listing_service_interface(): void
    {
        $reflection = new \ReflectionClass(InvoiceController::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'Constructor missing');
        $params = $constructor->getParameters();
        $this->assertCount(1, $params, 'Expected exactly one dependency');
        $type = $params[0]->getType();
        $this->assertNotNull($type, 'Missing type hint for dependency');
        $this->assertEquals(InvoiceListingApplicationServiceInterface::class, $type instanceof \ReflectionNamedType ? $type->getName() : null);
    }

    #[Test]
    public function expected_public_methods_exist(): void
    {
        $reflection = new \ReflectionClass(InvoiceController::class);
        $methodNames = collect($reflection->getMethods())
            ->filter(fn($m) => $m->isPublic() && $m->getDeclaringClass()->getName() === InvoiceController::class && $m->getName() !== '__construct')
            ->map->getName()
            ->values()
            ->all();

        sort($methodNames);
        $this->assertEquals(['getInvoice','getInvoices'], $methodNames, 'Unexpected public methods');
    }

    #[Test]
    public function get_invoice_method_signature_allows_optional_id(): void
    {
        $reflection = new \ReflectionMethod(InvoiceController::class, 'getInvoice');
        $params = $reflection->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('request', $params[0]->getName());
        $this->assertEquals('id', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional(), 'Route id parameter should be optional to allow query param fallback');
    }
}
