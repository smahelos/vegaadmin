<?php

namespace Tests\Unit\Notifications;

use App\Notifications\InvoiceDueReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InvoiceDueReminderTest extends TestCase
{
    #[Test]
    public function notification_extends_notification_class(): void
    {
        $reflection = new \ReflectionClass(InvoiceDueReminder::class);
        $this->assertTrue($reflection->isSubclassOf(Notification::class));
    }

    #[Test]
    public function notification_implements_should_queue(): void
    {
        $reflection = new \ReflectionClass(InvoiceDueReminder::class);
        $this->assertTrue($reflection->implementsInterface(ShouldQueue::class));
    }

    #[Test]
    public function notification_uses_queueable_trait(): void
    {
        $reflection = new \ReflectionClass(InvoiceDueReminder::class);
        $traits = $reflection->getTraitNames();
        $this->assertContains(Queueable::class, $traits);
    }

    #[Test]
    public function constructor_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(InvoiceDueReminder::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $this->assertCount(2, $constructor->getParameters());
        
        $params = $constructor->getParameters();
        $this->assertEquals('invoice', $params[0]->getName());
        $this->assertEquals('recipientType', $params[1]->getName());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertEquals('supplier', $params[1]->getDefaultValue());
    }

    #[Test]
    public function via_method_exists_and_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(InvoiceDueReminder::class);
        $method = $reflection->getMethod('via');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function to_mail_method_exists_and_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(InvoiceDueReminder::class);
        $method = $reflection->getMethod('toMail');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\Notifications\Messages\MailMessage', $returnType->getName());
    }

    #[Test]
    public function class_has_required_properties(): void
    {
        $reflection = new \ReflectionClass(InvoiceDueReminder::class);
        
        $this->assertTrue($reflection->hasProperty('invoice'));
        $this->assertTrue($reflection->hasProperty('recipientType'));
        
        $invoiceProperty = $reflection->getProperty('invoice');
        $this->assertTrue($invoiceProperty->isProtected());
        
        $recipientTypeProperty = $reflection->getProperty('recipientType');
        $this->assertTrue($recipientTypeProperty->isProtected());
    }
}
