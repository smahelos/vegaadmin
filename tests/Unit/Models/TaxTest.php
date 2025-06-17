<?php

namespace Tests\Unit\Models;

use App\Models\Tax;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TaxTest extends TestCase
{
    private Tax $tax;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tax = new Tax();
    }

    #[Test]
    public function model_has_correct_table_name(): void
    {
        $this->assertEquals('taxes', $this->tax->getTable());
    }

    #[Test]
    public function model_has_correct_guarded_attributes(): void
    {
        $this->assertContains('id', $this->tax->getGuarded());
    }

    #[Test]
    public function model_uses_correct_traits(): void
    {
        $traits = class_uses($this->tax);
        
        $this->assertContains(\Backpack\CRUD\app\Models\Traits\CrudTrait::class, $traits);
        $this->assertContains(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
    }

    #[Test]
    public function rate_formatted_accessor_method_exists(): void
    {
        $this->assertTrue(method_exists($this->tax, 'getRateFormattedAttribute'));
    }

    #[Test]
    public function rate_formatted_accessor_formats_correctly(): void
    {
        $this->tax->setRawAttributes(['rate' => 21.5]);
        
        $formatted = $this->tax->rate_formatted;
        
        $this->assertEquals('21.50%', $formatted);
    }

    #[Test]
    public function rate_formatted_accessor_handles_zero_rate(): void
    {
        $this->tax->setRawAttributes(['rate' => 0]);
        
        $formatted = $this->tax->rate_formatted;
        
        $this->assertEquals('0.00%', $formatted);
    }

    #[Test]
    public function rate_formatted_accessor_handles_decimal_places(): void
    {
        $this->tax->setRawAttributes(['rate' => 15.25]);
        
        $formatted = $this->tax->rate_formatted;
        
        $this->assertEquals('15.25%', $formatted);
    }

    #[Test]
    public function rate_formatted_accessor_handles_integer_rate(): void
    {
        $this->tax->setRawAttributes(['rate' => 10]);
        
        $formatted = $this->tax->rate_formatted;
        
        $this->assertEquals('10.00%', $formatted);
    }

    #[Test]
    public function model_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $this->tax);
    }

    #[Test]
    public function model_has_timestamps(): void
    {
        $this->assertTrue($this->tax->usesTimestamps());
    }

    #[Test]
    public function model_has_primary_key(): void
    {
        $this->assertEquals('id', $this->tax->getKeyName());
    }

    #[Test]
    public function model_key_type_is_int(): void
    {
        $this->assertEquals('int', $this->tax->getKeyType());
    }

    #[Test]
    public function model_is_incrementing(): void
    {
        $this->assertTrue($this->tax->getIncrementing());
    }
}
