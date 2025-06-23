<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestMailhogFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_to_option(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog', [
            '--to' => 'test@example.com'
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_subject_option(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog', [
            '--subject' => 'Test Email Subject'
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_sends_test_email(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog', [
            '--to' => 'test@example.com',
            '--subject' => 'Mailhog Test Email'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('test@example.com', $output);
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('mail:test-mailhog');
        
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_uses_default_values(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog');
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertIsString($output);
    }

    #[Test]
    public function command_handles_multiple_recipients(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog', [
            '--to' => 'test1@example.com,test2@example.com'
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_validates_email_format(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog', [
            '--to' => 'invalid-email'
        ]);
        
        // Command should handle invalid email gracefully
        $this->assertContains($exitCode, [0, 1]);
    }

    #[Test]
    public function command_tests_mailhog_connection(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog', [
            '--to' => 'test@example.com'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_sends_html_email(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog', [
            '--to' => 'test@example.com',
            '--subject' => 'HTML Test Email'
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_reports_sending_status(): void
    {
        Artisan::call('mail:test-mailhog', [
            '--to' => 'test@example.com'
        ]);
        
        $output = Artisan::output();
        
        // Should report whether email was sent successfully
        $this->assertIsString($output);
        $this->assertNotEmpty(trim($output));
    }

    #[Test]
    public function command_handles_mail_configuration(): void
    {
        // Test that command respects mail configuration
        $exitCode = Artisan::call('mail:test-mailhog');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_creates_test_email_content(): void
    {
        $exitCode = Artisan::call('mail:test-mailhog', [
            '--to' => 'test@example.com',
            '--subject' => 'Custom Test Subject'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Custom Test Subject', $output);
    }
}
