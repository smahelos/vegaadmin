<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Every translation key written literally in app/ and resources/views exists, so no user
 * sees a raw key. Billex keys must exist in all locales; keys of a vendor namespace
 * (backpack::…) only in Czech, the reference locale, because packages ship their own
 * locale sets. Keys built at runtime ("invoices.units.$unit") are not covered.
 */
final class TranslationKeysExistTest extends TestCase
{
    private const LOCALES = ['cs', 'de', 'en', 'sk'];

    private const DIRECTORIES = ['app', 'resources/views'];

    private const KEY_PATTERN = '/(?:__|trans|trans_choice|@lang)\(\s*([\'"])((?:\\\\.|(?!\1).)+?)\1\s*[,)]/';

    #[Test]
    public function every_literal_key_exists(): void
    {
        $missing = [];

        foreach ($this->literalKeys() as $key => $file) {
            $locales = str_contains($key, '::') ? ['cs'] : self::LOCALES;

            foreach ($locales as $locale) {
                if (! Lang::has($key, $locale, false)) {
                    $missing[] = "{$key} [{$locale}] in {$file}";
                }
            }
        }

        $this->assertSame([], $missing, "Missing translation keys:\n".implode("\n", $missing));
    }

    /**
     * @return array<string, string> key => first file using it
     */
    private function literalKeys(): array
    {
        $keys = [];

        foreach (self::DIRECTORIES as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory)));

            foreach ($files as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }

                preg_match_all(self::KEY_PATTERN, (string) file_get_contents($file->getPathname()), $matches);

                foreach ($matches[2] as $key) {
                    if (str_contains($key, '$') || str_contains($key, '{')) {
                        continue;
                    }

                    $keys[$key] ??= str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        return $keys;
    }
}
