<?php

namespace Tests\Feature\Helpers;

use App\Helpers\DateHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DateHelperFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function format_returns_czech_format_for_czech_locale(): void
    {
        App::setLocale('cs');
        
        $format = DateHelper::format();
        
        $this->assertEquals('d.m.Y', $format);
    }

    #[Test]
    public function format_returns_english_format_for_english_locale(): void
    {
        App::setLocale('en');
        
        $format = DateHelper::format();
        
        $this->assertEquals('Y-m-d', $format);
    }

    #[Test]
    public function format_returns_default_format_for_unknown_locale(): void
    {
        App::setLocale('fr'); // Not defined in the helper
        
        $format = DateHelper::format();
        
        $this->assertEquals('d.m.Y', $format); // Should fall back to default
    }

    #[Test]
    public function format_returns_default_format_for_empty_locale(): void
    {
        App::setLocale('');
        
        $format = DateHelper::format();
        
        $this->assertEquals('d.m.Y', $format);
    }

    #[Test]
    public function format_integrates_with_app_facade(): void
    {
        // Test multiple locale changes
        $locales = ['cs', 'en', 'de', 'sk'];
        $expectedFormats = [
            'cs' => 'd.m.Y',
            'en' => 'Y-m-d',
            'de' => 'd.m.Y', // Fallback to default
            'sk' => 'd.m.Y'  // Fallback to default
        ];
        
        foreach ($locales as $locale) {
            App::setLocale($locale);
            $format = DateHelper::format();
            $this->assertEquals($expectedFormats[$locale], $format, "Failed for locale: {$locale}");
        }
    }

    #[Test]
    public function format_handles_locale_persistence(): void
    {
        // Set locale and verify it persists across multiple calls
        App::setLocale('cs');
        
        $format1 = DateHelper::format();
        $format2 = DateHelper::format();
        $format3 = DateHelper::format();
        
        $this->assertEquals('d.m.Y', $format1);
        $this->assertEquals('d.m.Y', $format2);
        $this->assertEquals('d.m.Y', $format3);
        $this->assertEquals($format1, $format2);
        $this->assertEquals($format2, $format3);
    }

    #[Test]
    public function format_responds_to_locale_changes(): void
    {
        // Start with Czech
        App::setLocale('cs');
        $czechFormat = DateHelper::format();
        $this->assertEquals('d.m.Y', $czechFormat);
        
        // Change to English
        App::setLocale('en');
        $englishFormat = DateHelper::format();
        $this->assertEquals('Y-m-d', $englishFormat);
        
        // Back to Czech
        App::setLocale('cs');
        $czechFormatAgain = DateHelper::format();
        $this->assertEquals('d.m.Y', $czechFormatAgain);
        
        // Verify formats are different
        $this->assertNotEquals($czechFormat, $englishFormat);
        $this->assertEquals($czechFormat, $czechFormatAgain);
    }

    #[Test]
    public function format_returns_valid_date_formats(): void
    {
        $testLocales = ['cs', 'en', 'unknown'];
        
        foreach ($testLocales as $locale) {
            App::setLocale($locale);
            $format = DateHelper::format();
            
            // Should be a valid string
            $this->assertIsString($format);
            $this->assertNotEmpty($format);
            
            // Should contain date format characters
            $this->assertMatchesRegularExpression('/[dDjlNSwzWFmMntLoYyaABgGhHisuveIOPTZcrU]/', $format);
        }
    }

    #[Test]
    public function format_supports_configured_locales(): void
    {
        // Test the specific locales that are configured in the helper
        $supportedLocales = [
            'cs' => 'd.m.Y',
            'en' => 'Y-m-d'
        ];
        
        foreach ($supportedLocales as $locale => $expectedFormat) {
            App::setLocale($locale);
            $format = DateHelper::format();
            
            $this->assertEquals($expectedFormat, $format, "Locale {$locale} should return {$expectedFormat}");
        }
    }

    #[Test]
    public function format_maintains_consistency(): void
    {
        // Verify that the same locale always returns the same format
        $testRuns = 5;
        
        foreach (['cs', 'en'] as $locale) {
            App::setLocale($locale);
            $firstResult = DateHelper::format();
            
            for ($i = 0; $i < $testRuns; $i++) {
                $result = DateHelper::format();
                $this->assertEquals($firstResult, $result, "Format should be consistent for locale: {$locale}");
            }
        }
    }

    #[Test]
    public function format_works_with_real_date_formatting(): void
    {
        // Test that the returned formats actually work with PHP date functions
        $testDate = '2024-06-15';
        $timestamp = strtotime($testDate);
        
        App::setLocale('cs');
        $czechFormat = DateHelper::format();
        $czechFormatted = date($czechFormat, $timestamp);
        $this->assertEquals('15.06.2024', $czechFormatted);
        
        App::setLocale('en');
        $englishFormat = DateHelper::format();
        $englishFormatted = date($englishFormat, $timestamp);
        $this->assertEquals('2024-06-15', $englishFormatted);
    }
}
