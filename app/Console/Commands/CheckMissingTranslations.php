<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Command for checking missing translations between language files
 */
class CheckMissingTranslations extends Command
{
    /**
     * Command signature with default locale and compare locale
     */
    protected $signature = 'translations:check {locale=en} {--compare=cs}';
    
    /**
     * Command description
     */
    protected $description = 'Check for missing translations in a specific locale';

    /**
     * Execute the console command
     * 
     * @return int Exit code
     */
    public function handle(): int
    {
        $locale = $this->argument('locale');
        $compareLocale = $this->option('compare');
        
        // Validate locale names
        if (empty($locale) || !preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $locale)) {
            $this->error("Invalid locale format: '{$locale}'. Expected format: 'en' or 'en-US'");
            return 1;
        }
        
        if (empty($compareLocale) || !preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $compareLocale)) {
            $this->error("Invalid compare locale format: '{$compareLocale}'. Expected format: 'en' or 'en-US'");
            return 1;
        }
        
        $this->info("Checking missing translations in '{$locale}' compared to '{$compareLocale}'...");
        
        $localePath = lang_path($locale);
        $comparePath = lang_path($compareLocale);
        
        if (!File::exists($localePath)) {
            $this->error("Locale directory '{$localePath}' does not exist.");
            return 1;
        }
        
        if (!File::exists($comparePath)) {
            $this->error("Compare locale directory '{$comparePath}' does not exist.");
            return 1;
        }
        
        $files = File::files($comparePath);
        
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $localeFile = $localePath . '/' . $filename;
            
            if (!File::exists($localeFile)) {
                $this->warn("Missing file: {$filename} in {$locale} locale.");
                continue;
            }
            
            $compareTranslations = require $file->getPathname();
            $localeTranslations = require $localeFile;
            
            $this->checkMissingKeys($compareTranslations, $localeTranslations, $filename, '');
        }
        
        $this->info('Translation check completed.');
        
        return 0;
    }
    
    /**
     * Recursively check for missing translation keys
     * 
     * @param array $compare Reference translations array
     * @param array $locale Translation array to check
     * @param string $file Current file name
     * @param string $prefix Current key prefix for nested arrays
     * @return void
     */
    protected function checkMissingKeys(array $compare, array $locale, string $file, string $prefix = '')
    {
        foreach ($compare as $key => $value) {
            $currentKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                if (!isset($locale[$key]) || !is_array($locale[$key])) {
                    $this->warn("Missing key: {$currentKey} in {$file}");
                } else {
                    $this->checkMissingKeys($value, $locale[$key], $file, $currentKey);
                }
            } else {
                if (!isset($locale[$key])) {
                    $this->warn("Missing key: {$currentKey} in {$file}");
                }
            }
        }
    }
}
