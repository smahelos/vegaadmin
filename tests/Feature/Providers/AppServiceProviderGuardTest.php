<?php

namespace Tests\Feature\Providers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guard test ensuring AppServiceProvider does not contain domain-specific bindings.
 */
class AppServiceProviderGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Whitelist of allowed substrings (bindings) remaining in AppServiceProvider.
     * - Backpack user controller override
     * - Blade components / paginator config (not container bindings)
     * - Helper include
     */
    private array $allowedBindingFragments = [
        'Backpack\\PermissionManager\\app\\Http\\Controllers\\UserCrudController',
        'App\\Http\\Controllers\\Admin\\UserCrudController',
    ];

    #[Test]
    public function app_service_provider_contains_no_domain_contract_bindings(): void
    {
        $path = app_path('Providers/AppServiceProvider.php');
        $contents = file_get_contents($path);

        // Simplistic heuristic: no binding of */Domain/*/Contracts/* except allowed content override.
        // Match occurrences like \App\Domain\Xxx\Contracts\SomethingInterface::class inside bind calls
        $pattern = '/\\\\App\\\\Domain\\\\[A-Za-z0-9_\\\\]+\\\\Contracts\\\\[A-Za-z0-9_]+Interface::class/';
        preg_match_all($pattern, $contents, $matches);

        $violations = [];
        foreach ($matches[0] as $match) {
            $whitelisted = false;
            foreach ($this->allowedBindingFragments as $fragment) {
                if (str_contains($match, $fragment)) {
                    $whitelisted = true;
                    break;
                }
            }
            if (!$whitelisted) {
                $violations[] = $match;
            }
        }

        $this->assertEmpty($violations, 'AppServiceProvider should not contain domain contract bindings. Found: '.implode(', ', $violations));
    }
}
