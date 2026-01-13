<?php

namespace Tests\Unit\Domain\Payment\ValueObjects;

use App\Domain\Payment\ValueObjects\PaymentMetadata;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentMetadataTest extends TestCase
{
    #[Test]
    public function creates_metadata_with_valid_data(): void
    {
        $data = [
            'variable_symbol' => '1234567890',
            'message' => 'Test payment',
            'constant_symbol' => '0308',
        ];
        
        $metadata = new PaymentMetadata($data, 'qr_payment');
        
        $this->assertEquals('1234567890', $metadata->getVariableSymbol());
        $this->assertEquals('Test payment', $metadata->getMessage());
        $this->assertEquals('0308', $metadata->getConstantSymbol());
        $this->assertInstanceOf(Carbon::class, $metadata->getCreatedAt());
    }

    #[Test]
    public function creates_qr_payment_metadata(): void
    {
        $metadata = PaymentMetadata::forQrPayment(
            '1234567890',
            'QR Payment test',
            '0308',
            '123'
        );
        
        $this->assertEquals('1234567890', $metadata->getVariableSymbol());
        $this->assertEquals('QR Payment test', $metadata->getMessage());
        $this->assertEquals('0308', $metadata->getConstantSymbol());
        $this->assertEquals('123', $metadata->getSpecificSymbol());
    }

    #[Test]
    public function creates_bank_transfer_metadata(): void
    {
        $metadata = PaymentMetadata::forBankTransfer(
            '9876543210',
            'Bank transfer test',
            'REF123'
        );
        
        $this->assertEquals('9876543210', $metadata->getVariableSymbol());
        $this->assertEquals('Bank transfer test', $metadata->getMessage());
        $this->assertEquals('REF123', $metadata->getReference());
    }

    #[Test]
    public function creates_card_payment_metadata(): void
    {
        $metadata = PaymentMetadata::forCardPayment(
            'https://example.com/return',
            'https://example.com/notify',
            ['custom_field' => 'custom_value']
        );
        
        $this->assertEquals('https://example.com/return', $metadata->getReturnUrl());
        $this->assertEquals('https://example.com/notify', $metadata->getNotifyUrl());
        $this->assertEquals('custom_value', $metadata->get('custom_field'));
    }

    #[Test]
    public function gets_field_values(): void
    {
        $data = [
            'variable_symbol' => '1234567890',
            'message' => 'Test message',
            'custom_field' => 'custom_value',
        ];
        
        $metadata = new PaymentMetadata($data, 'qr_payment');
        
        $this->assertEquals('1234567890', $metadata->get('variable_symbol'));
        $this->assertEquals('Test message', $metadata->get('message'));
        $this->assertEquals('custom_value', $metadata->get('custom_field'));
        $this->assertEquals('default', $metadata->get('non_existent', 'default'));
        $this->assertNull($metadata->get('non_existent'));
    }

    #[Test]
    public function checks_field_existence(): void
    {
        $data = [
            'variable_symbol' => '1234567890',
            'message' => 'Test message',
        ];
        
        $metadata = new PaymentMetadata($data, 'qr_payment');
        
        $this->assertTrue($metadata->has('variable_symbol'));
        $this->assertTrue($metadata->has('message'));
        $this->assertFalse($metadata->has('non_existent'));
    }

    #[Test]
    public function converts_to_array(): void
    {
        $data = [
            'variable_symbol' => '1234567890',
            'message' => 'Test message',
        ];
        
        $metadata = new PaymentMetadata($data, 'qr_payment');
        
        $this->assertEquals($data, $metadata->toArray());
    }

    #[Test]
    public function adds_field_with_with_method(): void
    {
        $metadata = PaymentMetadata::forQrPayment('123', 'Test');
        $newMetadata = $metadata->with('new_field', 'new_value');
        
        $this->assertFalse($metadata->has('new_field'));
        $this->assertTrue($newMetadata->has('new_field'));
        $this->assertEquals('new_value', $newMetadata->get('new_field'));
    }

    #[Test]
    public function removes_field_with_without_method(): void
    {
        $metadata = PaymentMetadata::forQrPayment('123', 'Test', '0308');
        $newMetadata = $metadata->without('constant_symbol');
        
        $this->assertTrue($metadata->has('constant_symbol'));
        $this->assertFalse($newMetadata->has('constant_symbol'));
    }

    #[Test]
    public function merges_metadata(): void
    {
        $metadata1 = PaymentMetadata::forQrPayment('123', 'Test');
        $metadata2 = PaymentMetadata::forBankTransfer('456', 'Another test', 'REF');
        
        $merged = $metadata1->merge($metadata2);
        
        $this->assertEquals('456', $merged->getVariableSymbol()); // Overwritten
        $this->assertEquals('Another test', $merged->getMessage()); // Overwritten
        $this->assertEquals('REF', $merged->getReference()); // Added
    }

    #[Test]
    public function compares_metadata_equality(): void
    {
        $data = ['variable_symbol' => '123', 'message' => 'Test'];
        
        $metadata1 = new PaymentMetadata($data, 'qr_payment');
        $metadata2 = new PaymentMetadata($data, 'qr_payment');
        $metadata3 = new PaymentMetadata(['variable_symbol' => '456', 'message' => 'Test'], 'qr_payment');
        
        $this->assertTrue($metadata1->equals($metadata2));
        $this->assertFalse($metadata1->equals($metadata3));
    }

    #[Test]
    public function converts_to_json_string(): void
    {
        $data = [
            'variable_symbol' => '123',
            'message' => 'Test message',
        ];
        
        $metadata = new PaymentMetadata($data, 'qr_payment');
        $jsonString = (string) $metadata;
        
        $this->assertJson($jsonString);
        $decoded = json_decode($jsonString, true);
        $this->assertEquals($data, $decoded);
    }

    #[Test]
    public function sanitizes_data_removes_null_and_empty_values(): void
    {
        $data = [
            'variable_symbol' => '123',
            'message' => 'Test',
            'empty_string' => '',
            'null_value' => null,
            'whitespace_only' => '   ',
        ];
        
        $metadata = new PaymentMetadata($data, 'qr_payment');
        $result = $metadata->toArray();
        
        $this->assertArrayHasKey('variable_symbol', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayNotHasKey('empty_string', $result);
        $this->assertArrayNotHasKey('null_value', $result);
        $this->assertArrayNotHasKey('whitespace_only', $result);
    }

    #[Test]
    public function trims_string_values(): void
    {
        $data = [
            'variable_symbol' => '  123  ',
            'message' => '  Test message  ',
        ];
        
        $metadata = new PaymentMetadata($data, 'qr_payment');
        
        $this->assertEquals('123', $metadata->getVariableSymbol());
        $this->assertEquals('Test message', $metadata->getMessage());
    }

    #[Test]
    public function throws_exception_for_missing_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Required field 'variable_symbol' is missing");
        
        new PaymentMetadata(['message' => 'Test'], 'qr_payment');
    }

    #[Test]
    public function throws_exception_for_unknown_payment_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown payment type: unknown_type');
        
        new PaymentMetadata(['field' => 'value'], 'unknown_type');
    }

    #[Test]
    public function throws_exception_for_field_exceeding_length_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'message' exceeds maximum length");
        
        $longMessage = str_repeat('a', 141); // Exceeds 140 character limit
        
        new PaymentMetadata([
            'variable_symbol' => '123',
            'message' => $longMessage,
        ], 'qr_payment');
    }

    #[Test]
    public function validates_required_fields_for_different_payment_types(): void
    {
        // QR payment requires variable_symbol and message
        $qrData = ['variable_symbol' => '123', 'message' => 'Test'];
        $qrMetadata = new PaymentMetadata($qrData, 'qr_payment');
        $this->assertNotNull($qrMetadata);
        
        // Bank transfer requires only variable_symbol
        $bankData = ['variable_symbol' => '123'];
        $bankMetadata = new PaymentMetadata($bankData, 'bank_transfer');
        $this->assertNotNull($bankMetadata);
        
        // Card payment requires return_url
        $cardData = ['return_url' => 'https://example.com/return'];
        $cardMetadata = new PaymentMetadata($cardData, 'card_payment');
        $this->assertNotNull($cardMetadata);
        
        // GoPay requires return_url and notify_url
        $gopayData = [
            'return_url' => 'https://example.com/return',
            'notify_url' => 'https://example.com/notify',
        ];
        $gopayMetadata = new PaymentMetadata($gopayData, 'gopay');
        $this->assertNotNull($gopayMetadata);
    }

    #[Test]
    public function validates_field_length_limits(): void
    {
        $validData = [
            'variable_symbol' => '1234567890', // 10 chars - OK
            'message' => str_repeat('a', 140), // 140 chars - OK
            'constant_symbol' => '1234567890', // 10 chars - OK
            'specific_symbol' => '1234567890', // 10 chars - OK
            'reference' => str_repeat('b', 35), // 35 chars - OK
        ];
        
        $metadata = new PaymentMetadata($validData, 'qr_payment');
        $this->assertNotNull($metadata);
    }

    #[Test]
    public function creates_metadata_with_create_static_method(): void
    {
        $data = ['variable_symbol' => '123', 'message' => 'Test'];
        $metadata = PaymentMetadata::create($data, 'qr_payment');
        
        $this->assertEquals('123', $metadata->getVariableSymbol());
        $this->assertEquals('Test', $metadata->getMessage());
    }
}
