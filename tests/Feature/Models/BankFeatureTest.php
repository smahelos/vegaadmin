<?php

namespace Tests\Feature\Models;

use App\Models\Bank;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BankFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function bank_can_be_created_with_factory()
    {
        $bank = Bank::factory()->create();

        $this->assertInstanceOf(Bank::class, $bank);
        $this->assertDatabaseHas('banks', [
            'id' => $bank->id,
            'name' => $bank->name,
            'code' => $bank->code,
        ]);
    }

    #[Test]
    public function bank_serves_as_reference_data()
    {
        $bank = Bank::factory()->create([
            'code' => '0100',
            'name' => 'Czech National Bank'
        ]);

        // Bank serves as reference data for suppliers
        $this->assertDatabaseHas('banks', [
            'code' => '0100',
            'name' => 'Czech National Bank'
        ]);

        // Suppliers can reference this bank via bank_code field
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create([
            'user_id' => $user->id,
            'bank_code' => '0100'
        ]);

        $this->assertEquals('0100', $supplier->bank_code);
    }

    #[Test]
    public function bank_code_must_be_unique()
    {
        $bank1 = Bank::factory()->create(['code' => '1234']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Bank::factory()->create(['code' => '1234']);
    }

    #[Test]
    public function bank_can_be_created_with_minimal_data()
    {
        $bank = Bank::factory()->create([
            'name' => 'Test Bank',
            'code' => '5678',
            'country' => 'CZ',
        ]);

        $this->assertDatabaseHas('banks', [
            'name' => 'Test Bank',
            'code' => '5678',
            'country' => 'CZ',
            'active' => 1, // default value stored as integer in DB
        ]);
    }

    #[Test]
    public function bank_can_be_created_with_all_optional_fields()
    {
        $bank = Bank::factory()->create([
            'name' => 'Full Bank',
            'code' => '9999',
            'swift' => 'TESTCZ22',
            'country' => 'SK',
            'active' => false,
            'description' => 'Test bank description',
        ]);

        $this->assertDatabaseHas('banks', [
            'name' => 'Full Bank',
            'code' => '9999',
            'swift' => 'TESTCZ22',
            'country' => 'SK',
            'active' => 0,
            'description' => 'Test bank description',
        ]);
    }

    #[Test]
    public function bank_can_be_updated()
    {
        $bank = Bank::factory()->create([
            'name' => 'Original Bank',
            'active' => true,
        ]);

        $bank->update([
            'name' => 'Updated Bank',
            'active' => false,
        ]);

        $this->assertDatabaseHas('banks', [
            'id' => $bank->id,
            'name' => 'Updated Bank',
            'active' => 0,
        ]);
    }

    #[Test]
    public function bank_can_be_deleted()
    {
        $bank = Bank::factory()->create();
        $bankId = $bank->id;

        $bank->delete();

        $this->assertDatabaseMissing('banks', [
            'id' => $bankId,
        ]);
    }

    #[Test]
    public function bank_deletion_works_correctly()
    {
        $bank = Bank::factory()->create();
        $bankId = $bank->id;

        // Bank can be deleted since it's reference data
        $bank->delete();

        $this->assertDatabaseMissing('banks', [
            'id' => $bankId,
        ]);
    }

    #[Test]
    public function bank_factory_states_work_correctly()
    {
        $activeBank = Bank::factory()->active()->create();
        $inactiveBank = Bank::factory()->inactive()->create();
        $czechBank = Bank::factory()->czech()->create();

        $this->assertTrue($activeBank->active);
        $this->assertFalse($inactiveBank->active);
        $this->assertEquals('CZ', $czechBank->country);
        $this->assertStringContainsString('CZ', $czechBank->swift);
    }

    #[Test]
    public function bank_creation_with_timestamps()
    {
        $bank = Bank::factory()->create();

        $this->assertNotNull($bank->created_at);
        $this->assertNotNull($bank->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $bank->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $bank->updated_at);
    }
}
