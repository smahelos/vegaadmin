<?php

namespace Tests\Unit\Observers;

use App\Models\Invoice;
use App\Observers\InvoiceObserver;
use App\Services\InvoiceProductSyncService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceObserverTest extends TestCase
{
    private InvoiceObserver $observer;
    private $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock for InvoiceProductSyncService using Mockery
        $this->syncService = Mockery::mock(InvoiceProductSyncService::class);
        $this->observer = new InvoiceObserver($this->syncService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function observer_can_be_instantiated(): void
    {
        $this->assertInstanceOf(InvoiceObserver::class, $this->observer);
    }

    #[Test]
    public function observer_has_correct_constructor_dependency(): void
    {
        $reflection = new \ReflectionClass($this->observer);
        $property = $reflection->getProperty('syncService');
        $property->setAccessible(true);
        
        $this->assertSame($this->syncService, $property->getValue($this->observer));
    }

    #[Test]
    public function created_method_exists_and_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->observer);
        $method = $reflection->getMethod('created');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals('void', $method->getReturnType()?->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('invoice', $parameters[0]->getName());
        $this->assertEquals('App\Models\Invoice', $parameters[0]->getType()?->getName());
    }

    #[Test]
    public function updated_method_exists_and_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->observer);
        $method = $reflection->getMethod('updated');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals('void', $method->getReturnType()?->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('invoice', $parameters[0]->getName());
        $this->assertEquals('App\Models\Invoice', $parameters[0]->getType()?->getName());
    }

    #[Test]
    public function created_calls_sync_service(): void
    {
        // Create mock invoice
        $invoice = Mockery::mock('App\Models\Invoice');
        
        // Track that service was called
        $serviceCalled = false;
        $this->syncService
            ->shouldReceive('syncProductsFromJson')
            ->once()
            ->with($invoice)
            ->andReturnUsing(function() use (&$serviceCalled) {
                $serviceCalled = true;
                return null;
            });
        
        // Call the created method
        $this->observer->created($invoice);
        
        // Verify service was actually called
        $this->assertTrue($serviceCalled, 'InvoiceProductSyncService::syncProductsFromJson should be called');
    }

    #[Test]
    public function updated_calls_sync_service_when_invoice_text_is_dirty(): void
    {
        // Create mock invoice that reports invoice_text as dirty
        $invoice = Mockery::mock('App\Models\Invoice');
        $invoice->shouldReceive('isDirty')
                ->with('invoice_text')
                ->once()
                ->andReturn(true);
        
        // Track that service was called
        $serviceCalled = false;
        $this->syncService
            ->shouldReceive('syncProductsFromJson')
            ->once()
            ->with($invoice)
            ->andReturnUsing(function() use (&$serviceCalled) {
                $serviceCalled = true;
                return null;
            });
        
        // Call the updated method
        $this->observer->updated($invoice);
        
        // Verify service was actually called and dirty check was performed
        $this->assertTrue($serviceCalled, 'InvoiceProductSyncService::syncProductsFromJson should be called when invoice_text is dirty');
    }

    #[Test]
    public function updated_does_not_call_sync_service_when_invoice_text_is_not_dirty(): void
    {
        // Create mock invoice that reports invoice_text as not dirty
        $invoice = Mockery::mock('App\Models\Invoice');
        $invoice->shouldReceive('isDirty')
                ->with('invoice_text')
                ->once()
                ->andReturn(false);
        
        // Track that service was NOT called
        $serviceCalled = false;
        $this->syncService
            ->shouldNotReceive('syncProductsFromJson');
        
        // Call the updated method
        $this->observer->updated($invoice);
        
        // Verify service was NOT called
        $this->assertFalse($serviceCalled, 'InvoiceProductSyncService::syncProductsFromJson should NOT be called when invoice_text is not dirty');
    }

    #[Test]
    public function updated_only_checks_invoice_text_dirty_status(): void
    {
        // Create mock invoice
        $invoice = Mockery::mock('App\Models\Invoice');
        
        // Track that isDirty was called with correct parameter
        $dirtyCheckCalled = false;
        $invoice->shouldReceive('isDirty')
                ->with('invoice_text')
                ->once()
                ->andReturnUsing(function($field) use (&$dirtyCheckCalled) {
                    $dirtyCheckCalled = ($field === 'invoice_text');
                    return false;
                });
        
        // Call the updated method
        $this->observer->updated($invoice);
        
        // Verify isDirty was called with correct parameter
        $this->assertTrue($dirtyCheckCalled, 'isDirty should be called with "invoice_text" parameter');
    }
}
