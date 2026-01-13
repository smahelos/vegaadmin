<?php

namespace Tests\Feature\Providers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Structural coverage test ensuring every Domain/Application *Interface has a container binding.
 *
 * Scope:
 *  - app/Domain/*\/Contracts/*Interface.php
 *  - app/Application/*\/Contracts/*Interface.php (one or two depth levels)
 *
 * Skip List justifications:
 *  - PaymentGatewayInterface: Multiple concrete gateways registered under named keys, not directly resolved by interface.
 *  - QrPaymentProviderInterface: Implemented by provider classes discovered dynamically, not bound directly.
 *  - Invoice\Contracts\ClientRepositoryInterface & SupplierRepositoryInterface: Legacy duplicates superseded by Party domain repositories (keep until full removal).
 */
class DomainServiceProviderBindingsTest extends TestCase
{
    use RefreshDatabase; // Migrations give safer environment if any resolution touches DB models

    /** @var array<string> */
    private array $skip = [
        'App\\Domain\\Payment\\Contracts\\PaymentGatewayInterface',
        'App\\Domain\\Payment\\Contracts\\QrPaymentProviderInterface',
    // Removed legacy duplicates in Invoice domain (now only Party domain repositories exist)
    ];

    #[Test]
    public function all_domain_and_application_interfaces_are_bound(): void
    {
        $paths = [];
        // Domain interfaces
        $paths = array_merge($paths, glob(app_path('Domain/*/Contracts/*Interface.php')) ?: []);
        // Application layer interfaces (one level Application/<Context>/Contracts)
        $paths = array_merge($paths, glob(app_path('Application/*/Contracts/*Interface.php')) ?: []);
        // Application layer interfaces with an extra sub-namespace (e.g. Application/Invoice/Contracts already covered, but keep pattern flexible)
        $paths = array_unique($paths);

        $missingBindings = [];
        $resolutionErrors = [];

        foreach ($paths as $path) {
            $fqcn = $this->fqcnFromPath($path);

            if (in_array($fqcn, $this->skip, true)) {
                continue; // intentionally unmanaged binding
            }

            // Assert container has explicit binding (bound()). If not, mark missing.
            if (!app()->bound($fqcn)) {
                $missingBindings[] = $fqcn;
                continue; // do not attempt resolution
            }

            // Attempt to resolve & ensure instance implements interface
            try {
                $instance = app()->make($fqcn);
                if (!$instance instanceof $fqcn) {
                    $resolutionErrors[] = $fqcn.' resolved to '.get_debug_type($instance).' which does not implement it';
                }
            } catch (\Throwable $e) {
                $resolutionErrors[] = $fqcn.' threw: '.$e->getMessage();
            }
        }

        $messages = [];
        if ($missingBindings) {
            $messages[] = 'Missing bindings: '.implode(', ', $missingBindings);
        }
        if ($resolutionErrors) {
            $messages[] = 'Resolution errors: '.implode(', ', $resolutionErrors);
        }

        $this->assertEmpty($messages, "All interfaces should be bound & resolvable.\n".implode("\n", $messages));
    }

    private function fqcnFromPath(string $path): string
    {
        $relative = Str::after($path, app_path().DIRECTORY_SEPARATOR);
        $withoutPhp = Str::replaceLast('.php', '', $relative);
        return 'App\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $withoutPhp);
    }
}
