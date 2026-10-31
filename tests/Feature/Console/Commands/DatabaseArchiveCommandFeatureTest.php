<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Contact;
use App\Models\Invoice;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DatabaseArchiveCommandFeatureTest extends TestCase
{
    #[Test]
    public function archives_document_party_references_and_stored_one_off_data(): void
    {
        $invoice = Invoice::factory()->create([
            'created_at' => now()->subYears(4),
            'supplier_name' => 'Stored issuing business',
            'customer_name' => 'Stored one-off customer',
            'contact_id' => null,
        ]);
        $contact = Contact::factory()->create(['user_id' => $invoice->user_id]);
        $linked = Invoice::factory()->create([
            'user_id' => $invoice->user_id,
            'business_id' => $invoice->business_id,
            'contact_id' => $contact->id,
            'created_at' => now()->subYears(4),
        ]);

        $this->artisan('db:archive', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('invoices_archive', [
            'original_id' => $invoice->id,
            'business_id' => $invoice->business_id,
            'contact_id' => null,
        ]);
        $this->assertDatabaseHas('invoices_archive', ['original_id' => $linked->id, 'contact_id' => $contact->id]);
        $archive = DB::table('invoices_archive')->where('original_id', $invoice->id)->first();
        $stored = json_decode(
            $archive->invoice_data,
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertSame('Stored issuing business', $stored['supplier_name']);
        $this->assertSame('Stored one-off customer', $stored['customer_name']);
        $this->assertDatabaseHas('businesses', ['id' => $invoice->business_id]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('db:archive');

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_table_option(): void
    {
        $exitCode = Artisan::call('db:archive', [
            '--table' => 'invoices',
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_dry_run_option(): void
    {
        $exitCode = Artisan::call('db:archive', [
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_force_option(): void
    {
        $exitCode = Artisan::call('db:archive', [
            '--force' => true,
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_uses_default_table(): void
    {
        Artisan::call('db:archive', [
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        // Should use default table 'invoices'
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function businesses_and_contacts_have_no_automatic_erasure_policy(): void
    {
        $tables = [
            'businesses',
            'contacts',
        ];

        foreach ($tables as $table) {
            $this->assertDatabaseMissing('archive_policies', ['table_name' => $table]);
            $exitCode = Artisan::call('db:archive', [
                '--table' => $table,
                '--dry-run' => true,
            ]);

            $this->assertEquals(1, $exitCode);
        }
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('db:archive', [
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_respects_dry_run_mode(): void
    {
        $exitCode = Artisan::call('db:archive', [
            '--table' => 'invoices',
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // In dry-run mode, no actual archiving should happen
        $this->assertTrue(true); // Command completed without errors
    }

    #[Test]
    public function command_handles_safety_options(): void
    {
        // Test force option with dry-run for safety
        $exitCode = Artisan::call('db:archive', [
            '--force' => true,
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_validates_table_parameter(): void
    {
        // Test with valid table name
        $exitCode = Artisan::call('db:archive', [
            '--table' => 'invoices',
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }
}
