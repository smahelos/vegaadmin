<?php

namespace Tests\Unit\Domain\User\Services;

use App\Models\EntityLimit;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UniversalLimitServiceTest extends TestCase
{
    #[Test]
    public function entity_limit_constants(): void
    {
        $this->assertIsArray(EntityLimit::ENTITY_TYPES);
        $this->assertContains('invoice', EntityLimit::ENTITY_TYPES);
        $this->assertContains('client', EntityLimit::ENTITY_TYPES);
        $this->assertContains('supplier', EntityLimit::ENTITY_TYPES);
        $this->assertContains('product', EntityLimit::ENTITY_TYPES);
    }

    #[Test]
    public function metric_and_period_constants(): void
    {
        $this->assertIsArray(EntityLimit::METRIC_TYPES);
        $this->assertArrayHasKey('count', EntityLimit::METRIC_TYPES);
        $this->assertArrayHasKey('value', EntityLimit::METRIC_TYPES);
        $this->assertArrayHasKey('size', EntityLimit::METRIC_TYPES);

        $this->assertIsArray(EntityLimit::PERIOD_TYPES);
        $this->assertArrayHasKey('daily', EntityLimit::PERIOD_TYPES);
        $this->assertArrayHasKey('weekly', EntityLimit::PERIOD_TYPES);
        $this->assertArrayHasKey('monthly', EntityLimit::PERIOD_TYPES);
        $this->assertArrayHasKey('yearly', EntityLimit::PERIOD_TYPES);
        $this->assertArrayHasKey('lifetime', EntityLimit::PERIOD_TYPES);
    }

    #[Test]
    public function carbon_period_calculations(): void
    {
        Carbon::setTestNow('2025-01-15 14:30:00');
        $now = Carbon::now();
        $this->assertEquals('2025-01-15', $now->copy()->startOfDay()->toDateString());
        $this->assertEquals('2025-01-15', $now->copy()->endOfDay()->toDateString());
        $this->assertEquals('2025-01-13', $now->copy()->startOfWeek()->toDateString());
        $this->assertEquals('2025-01-19', $now->copy()->endOfWeek()->toDateString());
        $this->assertEquals('2025-01-01', $now->copy()->startOfMonth()->toDateString());
        $this->assertEquals('2025-01-31', $now->copy()->endOfMonth()->toDateString());
        $this->assertEquals('2025-01-01', $now->copy()->startOfYear()->toDateString());
        $this->assertEquals('2025-12-31', $now->copy()->endOfYear()->toDateString());
        Carbon::setTestNow();
    }

    #[Test]
    public function entity_limit_instantiation(): void
    {
        $limit = new EntityLimit();
        $this->assertInstanceOf(EntityLimit::class, $limit);
    }
}
