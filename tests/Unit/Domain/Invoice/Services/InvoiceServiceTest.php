<?php

namespace Tests\Unit\Services;

use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Resolve through container to satisfy constructor dependencies introduced in Wave 2
        $this->service = app(InvoiceService::class);
    }

    #[Test]
    public function ensure_object_properties_method_logic(): void
    {
        // Test that the method exists and can handle stdClass objects
        $object = new \stdClass();
        $properties = ['due_in', 'payment_method_id', 'test_property'];
        
        // This should not throw an exception
        $this->service->ensureObjectProperties($object, $properties);
        
        // Verify object properties were set
        $this->assertTrue(property_exists($object, 'due_in'));
        $this->assertTrue(property_exists($object, 'payment_method_id'));
        $this->assertTrue(property_exists($object, 'test_property'));
        $this->assertEquals(14, $object->due_in);
        $this->assertEquals(1, $object->payment_method_id);
        $this->assertEquals('', $object->test_property);
    }
}
