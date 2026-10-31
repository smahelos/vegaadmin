<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Bank;
use App\Models\BankAccount;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BankFeatureTest extends TestCase
{
    use WithFaker;

    #[Test]
    public function bank_can_be_created_with_factory(): void
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
    public function bank_serves_as_reference_data(): void
    {
        // Banks are reference data seeded in every environment
        $this->assertDatabaseHas('banks', [
            'code' => '0100',
            'name' => 'Komerční banka',
        ]);

        // Bank accounts reference the bank code
        $account = BankAccount::factory()->create([
            'bank_code' => '0100',
        ]);

        $this->assertEquals('0100', $account->bank_code);
    }

    #[Test]
    public function bank_code_must_be_unique(): void
    {
        $bank1 = Bank::factory()->create(['code' => '1234']);

        $this->expectException(QueryException::class);
        Bank::factory()->create(['code' => '1234']);
    }

    #[Test]
    public function bank_can_be_created_with_minimal_data(): void
    {
        $bank = Bank::factory()->create([
            'name' => 'Test Bank',
            'code' => '5678',
            'country' => 'CZ',
            'active' => true, // Explicitly set active to true
        ]);

        $this->assertDatabaseHas('banks', [
            'name' => 'Test Bank',
            'code' => '5678',
            'country' => 'CZ',
            'active' => 1, // default value stored as integer in DB
        ]);
    }

    #[Test]
    public function bank_can_be_created_with_all_optional_fields(): void
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
    public function bank_can_be_updated(): void
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
    public function bank_can_be_deleted(): void
    {
        $bank = Bank::factory()->create();
        $bankId = $bank->id;

        $bank->delete();

        $this->assertDatabaseMissing('banks', [
            'id' => $bankId,
        ]);
    }

    #[Test]
    public function bank_deletion_works_correctly(): void
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
    public function bank_factory_states_work_correctly(): void
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
    public function bank_creation_with_timestamps(): void
    {
        $bank = Bank::factory()->create();

        $this->assertNotNull($bank->created_at);
        $this->assertNotNull($bank->updated_at);
        $this->assertInstanceOf(Carbon::class, $bank->created_at);
        $this->assertInstanceOf(Carbon::class, $bank->updated_at);
    }
}
