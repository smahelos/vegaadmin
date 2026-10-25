<?php

namespace Tests\Feature\Domain\Invoice\Services;

use App\Application\Invoice\Services\InvoicePdfRenderer;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Services\InvoicePrintDataBuilder;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Snapshot style test for rendered Invoice PDF Blade templates (HTML before DomPDF conversion).
 * Ensures we detect unintended structural/formatting changes (totals, money component output, labels, order).
 *
 * Snapshot management strategy:
 * - Missing snapshot file: the HTML is written and the test is marked incomplete for manual review.
 * - Otherwise normalized HTML is compared with the stored snapshot (strict string match); files are never rewritten.
 * - To intentionally update snapshots: delete the snapshot files or run with UPDATE_PDF_SNAPSHOTS=1.
 *
 * Money formatting is locale-aware via intl; the environment needs full ICU data (see app.dockerfile).
 */
class InvoicePdfSnapshotTest extends TestCase
{
    private string $snapshotDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->snapshotDir = base_path('tests/Feature/Domain/Invoice/Services/__snapshots__');
        if (! is_dir($this->snapshotDir)) {
            mkdir(
                $this->snapshotDir,
                0775,
                true,
            );
        }
    }

    #[Test]
    public function pdf_templates_render_consistent_html_snapshots(): void
    {
        // Arrange deterministic data (no QR code to keep HTML stable)
        $supplier = Supplier::factory()->create([
            'account_number' => '',
            'bank_code' => '',
            'iban' => '',
            'name' => 'Snapshot Supplier s.r.o.',
        ]);
        $client = Client::factory()->create(['name' => 'Snapshot Client a.s.']);
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Bank Transfer']);
        $status = Status::factory()->create();

        $invoice = Invoice::factory()->create([
            'supplier_id' => $supplier->id,
            'client_id' => $client->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
            'invoice_vs' => 'SNAP-001',
            'invoice_ks' => '0308',
            'invoice_ss' => '1234',
            'payment_amount' => 1234.56,
            'payment_currency' => 'EUR',
        ]);

        $templates = [
            'default',
            'modern',
            'minimal',
        ];
        $failures = [];
        $created = [];

        foreach ($templates as $template) {
            $html = $this->renderTemplate(
                $invoice,
                $template,
                'cs',
            );
            $normalized = $this->normalizeHtml($html);
            // Remove dynamic supplier/client variable block (addresses & IDs) for stability
            $normalized = preg_replace(
                '/<div class="supplier-info">.*?<\\/div>/s',
                '<div class="supplier-info"><DYNAMIC_SUPPLIER_BLOCK/></div>',
                $normalized,
            );
            $normalized = preg_replace(
                '/<div class="client-info">.*?<\\/div>/s',
                '<div class="client-info"><DYNAMIC_CLIENT_BLOCK/></div>',
                $normalized,
            );
            $snapshotFile = $this->snapshotDir . '/invoice_' . $template . '.html';

            if (! file_exists($snapshotFile)) {
                file_put_contents($snapshotFile, $normalized);
                $created[] = $template;

                continue;
            }
            if (getenv('UPDATE_PDF_SNAPSHOTS')) {
                file_put_contents($snapshotFile, $normalized);

                continue;
            }

            $expected = file_get_contents($snapshotFile);
            if ($expected !== $normalized) {
                $failures[] = "Template {$template} snapshot mismatch:\n" . $this->stringDiff(
                    $expected,
                    $normalized,
                    5,
                );
            }
        }

        if (! empty($failures)) {
            $this->fail(implode("\n\n", $failures));
        }
        if (! empty($created)) {
            $this->markTestIncomplete('New snapshots written for review: ' . implode(', ', $created));
        }
        $this->assertTrue(true); // all matched
    }

    private function renderTemplate(
        Invoice $invoice,
        string $template,
        string $locale,
    ): string {
        // Build PrintableInvoiceData using DTO repo + builder, then capture renderer's view data
        $repo = app(InvoiceDtoReadRepositoryInterface::class);
        $builder = app(InvoicePrintDataBuilder::class);
        $renderer = app(InvoicePdfRenderer::class);
        $dto = $repo->findByIdAny(InvoiceId::fromInt($invoice->id));
        $printData = $builder->build($dto);

        $captured = null;
        // Return underlying concrete to satisfy facade's expected return type
        $concrete = app(\Barryvdh\DomPDF\PDF::class);
        Pdf::shouldReceive('loadView')->once()->with(
            'pdfs.templates.' . $template,
            \Mockery::on(function ($data) use (&$captured) {
                $captured = $data;

                return true;
            }),
        )->andReturn($concrete);
        // Invoke renderer (will trigger mocked loadView)
        $renderer->render(
            $printData,
            $template,
            $locale,
        );

        // Render Blade for snapshot using captured data
        return view('pdfs.templates.' . $template, $captured)->render();
    }

    private function normalizeHtml(string $html): string
    {
        // Remove excessive whitespace
        $html = preg_replace(
            '/\r\n|\r/',
            "\n",
            $html,
        );
        $html = preg_replace(
            '/[ \t]+/',
            ' ',
            $html,
        );
        $html = preg_replace(
            '/\n{2,}/',
            "\n",
            $html,
        );
        // Remove dynamic timestamps if any (placeholder pattern) - generic safeguard
        $html = preg_replace(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',
            '<DATETIME>',
            $html,
        );
        // Replace standalone dates in format dd.mm.YYYY (e.g. due dates, issue dates) with placeholder
        $html = preg_replace(
            '/\b\d{2}\.\d{2}\.\d{4}\b/',
            '<DATE>',
            $html,
        );
        // Replace generation time (HH:MM) following a date placeholder
        $html = preg_replace(
            '/<DATE> \d{2}:\d{2}\b/',
            '<DATE> <TIME>',
            $html,
        );
        // Replace Czech due_in value pattern (robust) "NN ... dní" with placeholder
        $html = preg_replace(
            '/<span class="info-value">\s*\d+[^<]*dní\s*<\/span>/',
            '<span class="info-value"><DUE_IN></span>',
            $html,
        );
        // Normalize supplier/client numeric lines (street, zip+city, ICO, DIC) to reduce snapshot churn
        $html = preg_replace(
            '/<p>[^<]*\d[^<]*<\/p>/',
            '<p><NUMERIC_LINE/></p>',
            $html,
        );
        // Normalize IČO / DIČ lines explicitly (in case numbers regex evolves)
        $html = preg_replace(
            '/<p>IČO:[^<]*<\/p>/',
            '<p><ICO_LINE/></p>',
            $html,
        );
        $html = preg_replace(
            '/<p>DIČ:[^<]*<\/p>/',
            '<p><DIC_LINE/></p>',
            $html,
        );
        // Normalize payment method translation keys or random slugs
        $html = preg_replace(
            '/payment_methods\.[a-zA-Z0-9_-]+/',
            'payment_methods.<METHOD>',
            $html,
        );
        // Normalize KS / SS numeric values (Konstantní symbol / Specifický symbol)
        $html = preg_replace(
            '/(<span class="info-label">Konstantní symbol:<\/span>\s*<span class="info-value">)\d+(<\/span>)/',
            '$1<KS>$2',
            $html,
        );
        $html = preg_replace(
            '/(<span class="info-label">SS:<\/span>\s*<span class="info-value">)\d+(<\/span>)/',
            '$1<SS>$2',
            $html,
        );
        // Strip possible base64 QR code content if present (not expected due to missing bank details)
        $html = preg_replace(
            '/data:image\/png;base64,[A-Za-z0-9+\/=]+/',
            'data:image/png;base64,<QR_PLACEHOLDER>',
            $html,
        );

        // (Party blocks are blanked higher up in test loop for stability)
        // Trim
        return trim($html);
    }

    private function stringDiff(
        string $expected,
        string $actual,
        int $contextLines = 3,
    ): string {
        $e = explode("\n", $expected);
        $a = explode("\n", $actual);
        $out = [];
        $len = max(count($e), count($a));
        for ($i = 0; $i < $len; $i++) {
            $exp = $e[$i] ?? '';
            $act = $a[$i] ?? '';
            if ($exp !== $act) {
                $start = max(0, $i - $contextLines);
                $end = min($len - 1, $i + $contextLines);
                $out[] = '@@ line ' . $i . ' @@';
                for ($j = $start; $j <= $end; $j++) {
                    $prefix = ($e[$j] ?? '') === ($a[$j] ?? '') ? ' ' : (($e[$j] ?? '') !== '' ? '-' : '+');
                    $out[] = $prefix . ' ' . ($a[$j] ?? '');
                }
                break; // show first mismatch only for brevity
            }
        }

        return $out ? implode("\n", $out) : '(no diff computed)';
    }
}
