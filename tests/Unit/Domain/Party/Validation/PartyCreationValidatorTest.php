<?php

namespace Tests\Unit\Domain\Party\Validation;

use App\Domain\Party\Validation\PartyCreationValidator;
use App\Domain\Party\Exceptions\PartyCreationException;
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PartyCreationValidatorTest extends TestCase
{
    private PartyCreationValidator $validator;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new PartyCreationValidator();
        // Use in-memory user instance to keep this a pure unit test (no DB access)
        $this->user = new User();
        $this->user->id = 1; // minimal identifier used by validator
    }

    #[Test]
    public function validate_client_prefixed_fields_success(): void
    {
        $data = [
            'client_name' => 'Client ABC',
            'client_email' => 'c@example.com',
            'client_city' => 'Prague'
        ];
        $payload = $this->validator->validateClient(new UserId($this->user->id), $data);
        $this->assertEquals('Client ABC', $payload['name']);
        $this->assertEquals('c@example.com', $payload['email']);
        $this->assertEquals($this->user->id, $payload['user_id']);
    }

    #[Test]
    public function validate_client_unprefixed_fields_success(): void
    {
        $data = [
            'name' => 'Normal Client',
            'email' => 'n@example.com'
        ];
        $payload = $this->validator->validateClient(new UserId($this->user->id), $data);
        $this->assertEquals('Normal Client', $payload['name']);
        $this->assertEquals('n@example.com', $payload['email']);
    }

    #[Test]
    public function validate_client_sets_default_country_when_missing(): void
    {
        $data = [
            'name' => 'Countryless Client'
        ];
        $payload = $this->validator->validateClient(new UserId($this->user->id), $data);
        $this->assertEquals('Countryless Client', $payload['name']);
        $this->assertEquals('CZ', $payload['country']);
    }

    #[Test]
    public function validate_client_prefixed_is_default_pass_through(): void
    {
        $data = [
            'client_name' => 'Client Defaulted',
            'client_is_default' => true
        ];
        $payload = $this->validator->validateClient(new UserId($this->user->id), $data);
        $this->assertTrue($payload['is_default']);
    }

    #[Test]
    public function validate_client_throws_on_short_name(): void
    {
        $this->expectException(PartyCreationException::class);
        $this->validator->validateClient(new UserId($this->user->id), ['client_name' => 'AB']);
    }

    #[Test]
    public function validate_supplier_success(): void
    {
        $data = [
            'name' => 'Supplier XYZ',
            'account_number' => '123456',
            'bank_code' => '0100'
        ];
        $payload = $this->validator->validateSupplier(new UserId($this->user->id), $data);
        $this->assertEquals('Supplier XYZ', $payload['name']);
        $this->assertTrue($payload['has_payment_info']);
    }

    #[Test]
    public function validate_supplier_has_payment_info_false_when_incomplete(): void
    {
        $data = [
            'name' => 'Supplier Incomplete',
            'account_number' => '123456'
        ];
        $payload = $this->validator->validateSupplier(new UserId($this->user->id), $data);
        $this->assertFalse($payload['has_payment_info']);
    }

    #[Test]
    public function validate_supplier_default_country_when_missing(): void
    {
        $data = [
            'name' => 'Countryless Supplier'
        ];
        $payload = $this->validator->validateSupplier(new UserId($this->user->id), $data);
        $this->assertEquals('CZ', $payload['country']);
    }

    #[Test]
    public function validate_supplier_has_payment_info_false_when_only_bank_code(): void
    {
        $data = [
            'name' => 'Supplier Partial',
            'bank_code' => '0100'
        ];
        $payload = $this->validator->validateSupplier(new UserId($this->user->id), $data);
        $this->assertFalse($payload['has_payment_info']);
    }

    #[Test]
    public function validate_supplier_throws_on_short_name(): void
    {
        $this->expectException(PartyCreationException::class);
        $this->validator->validateSupplier(new UserId($this->user->id), ['name' => 'AB']);
    }
}
