<?php

namespace Tests\Unit\Domain\Analytics\Events;

use App\Domain\Analytics\Events\AnalyticsDataChanged;
use App\Domain\Shared\Events\Contracts\DomainEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AnalyticsDataChangedTest extends TestCase
{
    #[Test]
    public function event_implements_domain_event_interface(): void
    {
        $event = new AnalyticsDataChanged(123, 'invoice', 456);
        
        $this->assertInstanceOf(DomainEvent::class, $event);
    }

    #[Test]
    public function event_stores_user_id_correctly(): void
    {
        $userId = 123;
        $event = new AnalyticsDataChanged($userId, 'invoice');
        
        $this->assertEquals($userId, $event->userId);
    }

    #[Test]
    public function event_stores_change_type_correctly(): void
    {
        $changeType = 'client';
        $event = new AnalyticsDataChanged(123, $changeType);
        
        $this->assertEquals($changeType, $event->changeType);
    }

    #[Test]
    public function event_stores_entity_id_correctly(): void
    {
        $entityId = 456;
        $event = new AnalyticsDataChanged(123, 'invoice', $entityId);
        
        $this->assertEquals($entityId, $event->entityId);
    }

    #[Test]
    public function event_uses_default_change_type_when_not_provided(): void
    {
        $event = new AnalyticsDataChanged(123);
        
        $this->assertEquals('general', $event->changeType);
    }

    #[Test]
    public function event_allows_null_entity_id(): void
    {
        $event = new AnalyticsDataChanged(123, 'statistics');
        
        $this->assertNull($event->entityId);
    }

    #[Test]
    public function event_has_occurred_on_timestamp(): void
    {
        $before = new \DateTimeImmutable('now');
        $event = new AnalyticsDataChanged(123, 'invoice');
        $after = new \DateTimeImmutable('now');
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->occurredOn);
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $event->occurredOn->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $event->occurredOn->getTimestamp());
    }

    #[Test]
    public function get_event_name_returns_class_name(): void
    {
        $event = new AnalyticsDataChanged(123);
        
        $this->assertEquals(AnalyticsDataChanged::class, $event->getEventName());
    }

    #[Test]
    public function get_event_id_returns_unique_string(): void
    {
        $event1 = new AnalyticsDataChanged(123);
        $event2 = new AnalyticsDataChanged(123);
        
        $this->assertIsString($event1->getEventId());
        $this->assertIsString($event2->getEventId());
        $this->assertNotEquals($event1->getEventId(), $event2->getEventId());
    }

    #[Test]
    public function get_payload_returns_complete_data(): void
    {
        $userId = 123;
        $changeType = 'client';
        $entityId = 456;
        $event = new AnalyticsDataChanged($userId, $changeType, $entityId);
        
        $payload = $event->getPayload();
        
        $this->assertIsArray($payload);
        $this->assertEquals($userId, $payload['user_id']);
        $this->assertEquals($changeType, $payload['change_type']);
        $this->assertEquals($entityId, $payload['entity_id']);
        $this->assertArrayHasKey('occurred_on', $payload);
        $this->assertIsString($payload['occurred_on']);
    }

    #[Test]
    public function get_occurred_on_returns_immutable_datetime(): void
    {
        $event = new AnalyticsDataChanged(123);
        
        $occurredOn = $event->getOccurredOn();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $occurredOn);
        $this->assertEquals($event->occurredOn, $occurredOn);
    }

    #[Test]
    public function event_with_all_change_types(): void
    {
        $changeTypes = ['invoice', 'client', 'supplier', 'statistics', 'monthly', 'dashboard'];
        
        foreach ($changeTypes as $changeType) {
            $event = new AnalyticsDataChanged(123, $changeType, 456);
            
            $this->assertEquals($changeType, $event->changeType);
            $this->assertEquals(123, $event->userId);
            $this->assertEquals(456, $event->entityId);
        }
    }
}
