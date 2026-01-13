<?php

namespace Tests\Feature\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Structural guard: ensure markAsPaid() remains a thin delegation to changeStatus('paid')
 * via InvoiceFrontendActionsApplicationService to prevent logic drift or duplication.
 */
class InvoiceControllerMarkAsPaidDelegationTest extends TestCase
{
    private string $controllerPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerPath = app_path('Http/Controllers/Frontend/InvoiceController.php');
        $this->assertFileExists($this->controllerPath, 'InvoiceController file missing');
    }

    #[Test]
    public function mark_as_paid_method_delegates_to_change_status_paid(): void
    {
        $source = file_get_contents($this->controllerPath);
        // Extract markAsPaid method body (naive regex but sufficient for guard; stable formatting expected)
        $this->assertMatchesRegularExpression('/function\s+markAsPaid\s*\(.*?\)\s*\{.*?\}/s', $source, 'markAsPaid method structure not found');

        // Ensure there is NO direct call to markInvoiceAsPaid or changeInvoiceStatus on application service (should go through frontend actions)
        $this->assertStringNotContainsString('->markInvoiceAsPaid(', $source, 'Direct markInvoiceAsPaid usage detected – should delegate through changeStatus');
        $this->assertStringNotContainsString('->changeInvoiceStatus(', $source, 'Direct changeInvoiceStatus usage detected – should delegate via frontend actions service');

        // Ensure delegation to frontend actions changeStatus with paid slug exists
        $this->assertStringContainsString('->changeStatus($user, $id, \'paid\')', $source, 'Expected delegation call to changeStatus($user, $id, \'paid\') missing');

        // Guard against future added branching logic (heuristic: method body should not contain multiple if statements besides initial user check)
        if (preg_match('/function\s+markAsPaid\s*\(.*?\)\s*\{(.*?)\}/s', $source, $m)) {
            $body = $m[1];
            $ifCount = preg_match_all('/\bif\s*\(/', $body, $tmp);
            $this->assertLessThanOrEqual(2, $ifCount, 'markAsPaid method contains more than 2 if statements; consider refactoring to keep it thin');
        }
    }
}
