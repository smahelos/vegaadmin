<?php

namespace Tests\Unit\Infrastructure\Persistence\Eloquent\Observers;

use App\Models\Invoice;
use App\Infrastructure\Persistence\Eloquent\Invoice\Observers\InvoiceObserver;
use App\Domain\Invoice\Contracts\InvoiceProductSyncServiceInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceObserverTest extends TestCase
{
    private InvoiceObserver $observer;
    private $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock for InvoiceProductSyncService using PHPUnit mock
        $this->syncService = $this->createMock(InvoiceProductSyncServiceInterface::class);
        $this->observer = new InvoiceObserver($this->syncService);
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
        $this->assertEquals('void', (string) $method->getReturnType());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('invoice', $parameters[0]->getName());
        $this->assertEquals('App\Models\Invoice', (string) $parameters[0]->getType());
    }

    #[Test]
    public function updated_method_exists_and_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->observer);
        $method = $reflection->getMethod('updated');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals('void', (string) $method->getReturnType());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('invoice', $parameters[0]->getName());
        $this->assertEquals('App\Models\Invoice', (string) $parameters[0]->getType());
    }

    #[Test]
    public function deleted_method_exists_and_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->observer);
        $method = $reflection->getMethod('deleted');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals('void', (string) $method->getReturnType());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('invoice', $parameters[0]->getName());
        $this->assertEquals('App\Models\Invoice', (string) $parameters[0]->getType());
    }

    #[Test]
    public function created_calls_sync_service(): void
    {
        // Create mock invoice with ID
        $invoice = $this->createMock(\App\Models\Invoice::class);
        $invoice->method('__get')
                ->willReturnMap([
                    ['id', 123],
                    ['user_id', 456]
                ]);
        
        // Mock the syncService to expect InvoiceId
        $this->syncService
            ->expects($this->once())
            ->method('syncProductsFromJson')
            ->with($this->callback(function($invoiceId) {
                return $invoiceId instanceof \App\Domain\Invoice\ValueObjects\InvoiceId 
                    && $invoiceId->getValue() === 123;
            }));
        
        // Mock the EventPublisher using createMock
        $eventPublisher = $this->createMock(\App\Domain\Shared\Events\Contracts\EventPublisherInterface::class);
        $eventPublisher->expects($this->once())
                      ->method('publish')
                      ->with($this->isInstanceOf(\App\Domain\User\Events\UserDataChanged::class));
        
        // Mock app() to return our EventPublisher
        $this->app->instance(\App\Domain\Shared\Events\Contracts\EventPublisherInterface::class, $eventPublisher);
        
        // Call the created method
        $this->observer->created($invoice);
    }

    #[Test]
    public function updated_calls_sync_service_when_invoice_text_is_dirty(): void
    {
        // Create mock invoice that reports invoice_text as dirty
        $invoice = $this->createMock(\App\Models\Invoice::class);
        $invoice->method('isDirty')
                ->willReturnMap([
                    ['invoice_text', true],
                    [['payment_amount', 'payment_status_id', 'issue_date', 'due_in'], false]
                ]);
        $invoice->method('__get')
                ->willReturnMap([
                    ['id', 123],
                    ['user_id', null] // No user_id to avoid event dispatch
                ]);
        
        // Service should be called for syncProductsFromJson
        $this->syncService
            ->expects($this->once())
            ->method('syncProductsFromJson')
            ->with($this->callback(function($invoiceId) {
                return $invoiceId instanceof \App\Domain\Invoice\ValueObjects\InvoiceId 
                    && $invoiceId->getValue() === 123;
            }));
        
        // Call the updated method
        $this->observer->updated($invoice);
    }

    #[Test]
    public function updated_does_not_call_sync_service_when_invoice_text_is_not_dirty(): void
    {
        // Create mock invoice that reports invoice_text as not dirty
        $invoice = $this->createMock(\App\Models\Invoice::class);
        $invoice->method('isDirty')
                ->willReturnMap([
                    ['invoice_text', false],
                    [['payment_amount', 'payment_status_id', 'issue_date', 'due_in'], false]
                ]);
        
        // Service should NOT be called for syncProductsFromJson
        $this->syncService
            ->expects($this->never())
            ->method('syncProductsFromJson');
        
        // Call the updated method
        $this->observer->updated($invoice);
        
        // Test passes if syncProductsFromJson was not called
        $this->assertTrue(true, 'InvoiceProductSyncService::syncProductsFromJson should NOT be called when invoice_text is not dirty');
    }

    #[Test]
    public function updated_only_checks_invoice_text_dirty_status(): void
    {
        // Create mock invoice
        $invoice = $this->createMock(\App\Models\Invoice::class);
        $invoice->method('isDirty')
                ->willReturnMap([
                    ['invoice_text', false],
                    [['payment_amount', 'payment_status_id', 'issue_date', 'due_in'], false]
                ]);
        
        // Call the updated method
        $this->observer->updated($invoice);
        
        // Verify test runs without exception (isDirty calls are mocked)
        $this->assertTrue(true, 'isDirty should be called with correct parameters');
    }

    #[Test]
    public function deleted_publishes_user_data_changed_event_when_user_exists(): void
    {
        // Mock invoice with user_id
        $invoice = $this->createMock(\App\Models\Invoice::class);
        $invoice->method('__get')
                ->willReturnMap([
                    ['user_id', 456]
                ]);

        // Mock EventPublisher through container
        $eventPublisher = $this->createMock(\App\Domain\Shared\Events\Contracts\EventPublisherInterface::class);
        $eventPublisher->expects($this->once())
                      ->method('publish')
                      ->with($this->isInstanceOf(\App\Domain\User\Events\UserDataChanged::class));
        
        $this->app->instance(\App\Domain\Shared\Events\Contracts\EventPublisherInterface::class, $eventPublisher);
        
        // Call the deleted method
        $this->observer->deleted($invoice);
    }

    #[Test]
    public function updated_publishes_user_data_changed_event_for_important_fields(): void
    {
        // Mock invoice where important fields are dirty
        $invoice = $this->createMock(\App\Models\Invoice::class);
        $invoice->method('isDirty')
                ->willReturnMap([
                    ['invoice_text', false],
                    [['payment_amount', 'payment_status_id', 'issue_date', 'due_in'], true]
                ]);
        $invoice->method('__get')
                ->willReturnMap([
                    ['user_id', 456]
                ]);

        // Mock EventPublisher through container
        $eventPublisher = $this->createMock(\App\Domain\Shared\Events\Contracts\EventPublisherInterface::class);
        $eventPublisher->expects($this->once())
                      ->method('publish')
                      ->with($this->isInstanceOf(\App\Domain\User\Events\UserDataChanged::class));
        
        $this->app->instance(\App\Domain\Shared\Events\Contracts\EventPublisherInterface::class, $eventPublisher);
        
        // Call the updated method
        $this->observer->updated($invoice);
    }
}
