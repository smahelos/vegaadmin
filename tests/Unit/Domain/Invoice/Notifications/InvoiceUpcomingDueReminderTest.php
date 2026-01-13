<?php

namespace Tests\Unit\Infrastructure\Notifications;

use App\Infrastructure\Notifications\Invoice\Messages\InvoiceUpcomingDueReminder;
use App\Domain\Invoice\Notifications\DTO\InvoiceReminderPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InvoiceUpcomingDueReminderTest extends TestCase
{
    #[Test]
    public function notification_extends_notification_class(): void
    {
        $reflection = new \ReflectionClass(InvoiceUpcomingDueReminder::class);
        $this->assertTrue($reflection->isSubclassOf(Notification::class));
    }

    #[Test]
    public function notification_implements_should_queue(): void
    {
        $reflection = new \ReflectionClass(InvoiceUpcomingDueReminder::class);
        $this->assertTrue($reflection->implementsInterface(ShouldQueue::class));
    }

    #[Test]
    public function notification_uses_queueable_trait(): void
    {
        $reflection = new \ReflectionClass(InvoiceUpcomingDueReminder::class);
        $traits = $reflection->getTraitNames();
        $this->assertContains(Queueable::class, $traits);
    }

    #[Test]
    public function constructor_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(InvoiceUpcomingDueReminder::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
    $this->assertCount(1, $constructor->getParameters());
        
    $params = $constructor->getParameters();
    $this->assertEquals('payload', $params[0]->getName());
    $type = $params[0]->getType();
    $this->assertNotNull($type);
    $this->assertInstanceOf(\ReflectionNamedType::class, $type);
    $this->assertEquals(InvoiceReminderPayload::class, $type->getName());
    }

    #[Test]
    public function via_method_exists_and_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(InvoiceUpcomingDueReminder::class);
        $method = $reflection->getMethod('via');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('array', $returnType->getName());
        }
    }

    #[Test]
    public function to_mail_method_exists_and_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass(InvoiceUpcomingDueReminder::class);
        $method = $reflection->getMethod('toMail');
        
        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('Illuminate\\Notifications\\Messages\\MailMessage', $returnType->getName());
        }
    }

    #[Test]
    public function class_has_required_properties(): void
    {
    $reflection = new \ReflectionClass(InvoiceUpcomingDueReminder::class);
        
    $this->assertTrue($reflection->hasProperty('payload'));
    $payloadProperty = $reflection->getProperty('payload');
    $this->assertTrue($payloadProperty->isPrivate());
    }
}
