<?php

namespace Tests\Unit\Models;

use App\Models\Bank;
use App\Models\Supplier;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BankTest extends TestCase
{
    #[Test]
    public function bank_has_correct_fillable_attributes()
    {
        $bank = new Bank();
        
        $expected = [
            'name', 'code', 'swift', 'country', 'active', 'description'
        ];
        
        $this->assertEquals($expected, $bank->getFillable());
    }

    #[Test]
    public function bank_has_correct_guarded_attributes()
    {
        $bank = new Bank();
        
        // With fillable defined, guarded should be ['*'] by default
        $this->assertEquals(['*'], $bank->getGuarded());
    }

    #[Test]
    public function bank_has_correct_table_name()
    {
        $bank = new Bank();
        
        $this->assertEquals('banks', $bank->getTable());
    }

    #[Test]
    public function bank_uses_correct_traits()
    {
        $bank = new Bank();
        
        $this->assertContains(CrudTrait::class, class_uses($bank));
        $this->assertContains(HasFactory::class, class_uses($bank));
    }

    #[Test]
    public function bank_has_correct_casts()
    {
        $bank = new Bank();
        
        $expected = [
            'active' => 'boolean',
            'id' => 'int',
        ];
        
        $this->assertEquals($expected, $bank->getCasts());
    }

    #[Test]
    public function bank_reference_data_structure_is_correct()
    {
        $bank = new Bank();
        
        // Bank model serves as reference data for bank codes
        $this->assertTrue(method_exists($bank, 'getFillable'));
        $this->assertContains('code', $bank->getFillable());
        $this->assertContains('name', $bank->getFillable());
    }
}
