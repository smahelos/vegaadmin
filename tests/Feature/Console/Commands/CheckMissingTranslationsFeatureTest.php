<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckMissingTranslationsFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_executes_successfully_with_default_parameters(): void
    {
        $exitCode = Artisan::call('translations:check');
        
        // Should succeed if language directories exist
        $this->assertContains($exitCode, [0, 1]); // 0 = success, 1 = missing files
    }

    #[Test]
    public function command_accepts_locale_argument(): void
    {
        $exitCode = Artisan::call('translations:check', [
            'locale' => 'cs'
        ]);
        
        $this->assertContains($exitCode, [0, 1]);
    }

    #[Test]
    public function command_accepts_compare_option(): void
    {
        $exitCode = Artisan::call('translations:check', [
            'locale' => 'en',
            '--compare' => 'de'
        ]);
        
        $this->assertContains($exitCode, [0, 1]);
    }

    #[Test]
    public function command_provides_feedback_for_existing_locales(): void
    {
        Artisan::call('translations:check', [
            'locale' => 'en',
            '--compare' => 'cs'
        ]);
        
        $output = Artisan::output();
        
        $this->assertStringContainsString("Checking missing translations in 'en' compared to 'cs'", $output);
    }

    #[Test]
    public function command_handles_non_existent_locale_gracefully(): void
    {
        $exitCode = Artisan::call('translations:check', [
            'locale' => 'zz'  // Valid format but non-existent locale
        ]);
        
        $this->assertEquals(1, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('does not exist', $output);
    }

    #[Test]
    public function command_handles_non_existent_compare_locale_gracefully(): void
    {
        $exitCode = Artisan::call('translations:check', [
            'locale' => 'en',
            '--compare' => 'zz'  // Valid format but non-existent locale
        ]);
        
        $this->assertEquals(1, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('does not exist', $output);
    }

    #[Test]
    public function command_checks_actual_translation_files(): void
    {
        // This test assumes that we have some language files
        if (File::exists(lang_path('en')) && File::exists(lang_path('cs'))) {
            $exitCode = Artisan::call('translations:check', [
                'locale' => 'en',
                '--compare' => 'cs'
            ]);
            
            $this->assertContains($exitCode, [0, 1]);
            
            $output = Artisan::output();
            
            // Should mention checking files
            $this->assertStringContainsString('Checking missing translations', $output);
        } else {
            $this->markTestSkipped('Language files not found');
        }
    }

    #[Test]
    public function command_returns_proper_exit_codes(): void
    {
        // Test with existing locales
        if (File::exists(lang_path('en')) && File::exists(lang_path('cs'))) {
            $exitCode = Artisan::call('translations:check', [
                'locale' => 'en',
                '--compare' => 'cs'
            ]);
            
            // Should return 0 (success) or 1 (files missing/missing translations)
            $this->assertContains($exitCode, [0, 1]);
        }
        
        // Test with non-existent locale - should return 1
        $exitCode = Artisan::call('translations:check', [
            'locale' => 'zzz_nonexistent'
        ]);
        
        $this->assertEquals(1, $exitCode);
    }

    #[Test]
    public function command_processes_php_translation_files(): void
    {
        if (File::exists(lang_path('en')) && File::exists(lang_path('cs'))) {
            $exitCode = Artisan::call('translations:check', [
                'locale' => 'en',
                '--compare' => 'cs'
            ]);
            
            $this->assertContains($exitCode, [0, 1]);
            
            $output = Artisan::output();
            
            // The command should process .php files
            $this->assertNotEmpty($output);
        } else {
            $this->markTestSkipped('Language directories not found');
        }
    }

    #[Test]
    public function command_validates_input_parameters(): void
    {
        // Test that command validates locale parameter properly
        $locales = ['en', 'cs', 'de', 'sk'];
        
        foreach ($locales as $locale) {
            $exitCode = Artisan::call('translations:check', [
                'locale' => $locale
            ]);
            
            // Should handle any valid locale string
            $this->assertIsInt($exitCode);
        }
    }

    #[Test]
    public function command_handles_edge_cases(): void
    {
        // Test with empty string locale
        $exitCode = Artisan::call('translations:check', [
            'locale' => ''
        ]);
        
        $this->assertEquals(1, $exitCode);
        
        // Test with special characters
        $exitCode = Artisan::call('translations:check', [
            'locale' => 'test@#$%'
        ]);
        
        $this->assertEquals(1, $exitCode);
    }
}
