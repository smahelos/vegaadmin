<?php

namespace Tests\Feature\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ensures Frontend InvoiceController only references allowed Application / Domain service interfaces
 * (prevents accidental direct use of implementations or forbidden services that would bloat controller logic).
 */
class InvoiceControllerAllowedServicesTest extends TestCase
{
    private string $controllerRelPath = 'Http/Controllers/Frontend/InvoiceController.php';

    /** @var array<int,string> Fully qualified interface names allowed to appear in use statements */
    private array $allowedInterfaces = [
        'App\\Domain\\Invoice\\Contracts\\InvoiceServiceInterface',
        'App\\Domain\\Invoice\\Contracts\\InvoicePdfServiceInterface',
        'App\\Domain\\User\\Contracts\\LocaleServiceInterface',
        'App\\Domain\\Payment\\Contracts\\BankServiceInterface',
        'App\\Application\\Invoice\\Contracts\\GuestInvoiceApplicationServiceInterface',
        'App\\Application\\Invoice\\Contracts\\InvoiceFrontendActionsApplicationServiceInterface',
        'App\\Application\\Invoice\\Contracts\\InvoiceFormApplicationServiceInterface',
        'App\\Domain\\Party\\Contracts\\InvoicePartyServiceInterface',
    // Added interfaces currently used in Frontend InvoiceController
    'App\\Application\\Invoice\\Contracts\\InvoiceRequestAssemblerInterface',
    'App\\Application\\Invoice\\Contracts\\InvoiceMutationApplicationServiceInterface',
    'App\\Application\\Invoice\\Contracts\\InvoicePdfApplicationServiceInterface',
    ];

    /** Implementation class patterns that must NOT be imported directly */
    private array $forbiddenConcretePatterns = [
        '/use\\s+App\\\\Domain\\\\Invoice\\\\Services\\\\[A-Za-z0-9_]+Service;/',
        '/use\\s+App\\\\Domain\\\\Payment\\\\Services\\\\[A-Za-z0-9_]+Service;/',
        '/use\\s+App\\\\Domain\\\\User\\\\Services\\\\[A-Za-z0-9_]+Service;/',
        '/use\\s+App\\\\Application\\\\Invoice\\\\Services\\\\[A-Za-z0-9_]+ApplicationService;/'
    ];

    #[Test]
    public function invoice_controller_only_uses_allowed_service_interfaces(): void
    {
        $path = app_path($this->controllerRelPath);
        $this->assertFileExists($path, 'InvoiceController not found');
        $code = file_get_contents($path);

        // Collect all use statements for App namespace
        preg_match_all('/^use\\s+(App\\\\[^;]+);/m', $code, $matches);
        $imports = $matches[1] ?? [];

        $violations = [];
        foreach ($imports as $fqcn) {
            if (!str_starts_with($fqcn, 'App\\')) { continue; }
            // skip non-service imports (Requests, Models, Facades, DTO, Controller base, etc.)
            if (!str_contains($fqcn, 'Contracts') && !str_contains($fqcn, 'Application\\Invoice\\Contracts')) { continue; }
            if (!in_array($fqcn, $this->allowedInterfaces, true)) {
                $violations[] = "Unexpected service contract import: $fqcn";
            }
        }

        foreach ($this->forbiddenConcretePatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                $violations[] = 'Forbidden concrete service import matched pattern: '.$pattern;
            }
        }

        if (!empty($violations)) {
            $this->fail("InvoiceController service usage violations:\n".implode("\n", $violations));
        }
        $this->assertTrue(true);
    }
}
