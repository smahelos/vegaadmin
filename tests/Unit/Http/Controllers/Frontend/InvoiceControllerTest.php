<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\InvoiceController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    #[Test]
    public function constructor_has_six_parameters(): void
    {
        $reflection = new \ReflectionClass(InvoiceController::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(6, $constructor->getParameters());
    }

    #[Test]
    public function constructor_requires_expected_service_interfaces(): void
    {
        $reflection = new \ReflectionClass(InvoiceController::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $expectedTypes = [
            'App\\Application\\Invoice\\Contracts\\InvoiceFormApplicationServiceInterface',
            'App\\Application\\Invoice\\Contracts\\GuestInvoiceApplicationServiceInterface',
            'App\\Application\\Invoice\\Contracts\\InvoiceFrontendActionsApplicationServiceInterface',
            'App\\Application\\Invoice\\Contracts\\InvoiceRequestAssemblerInterface',
            'App\\Application\\Invoice\\Contracts\\InvoiceMutationApplicationServiceInterface',
            'App\\Application\\Invoice\\Contracts\\InvoicePdfApplicationServiceInterface',
        ];
        
        foreach ($parameters as $index => $parameter) {
            $type = $parameter->getType();
            $this->assertNotNull($type, "Parameter {$index} should have type");
            if ($type instanceof \ReflectionNamedType) {
                $this->assertEquals($expectedTypes[$index], $type->getName());
            }
        }
    }
}
