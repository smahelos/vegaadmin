<?php

namespace Tests\Feature\Providers;

use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifies each Domain/Application interface is bound inside the EXPECTED Domain Service Provider file.
 * Expected provider heuristics:
 *  - Domain interfaces: app/Domain/<Domain>/Contracts/FooInterface.php -> App\Providers\<Domain>DomainServiceProvider
 *  - Application interfaces: app/Application/<Context>/Contracts/FooInterface.php -> App\Providers\<Context>DomainServiceProvider
 *    (Only enforced if provider file exists.)
 * Skip list covers interfaces that are intentionally resolved differently (dynamic gateway discovery, etc.).
 */
class DomainServiceProviderBindingOriginTest extends TestCase
{
    /** @var array<string> */
    private array $skip = [
        'App\\Domain\\Payment\\Contracts\\PaymentGatewayInterface',
        'App\\Domain\\Payment\\Contracts\\QrPaymentProviderInterface',
    ];

    /** @var array<string,string> interface => providerFqcn */
    private array $bindingOrigins = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->bindingOrigins = $this->scanProviderBindings();
    }

    #[Test]
    public function interfaces_are_bound_in_expected_domain_provider(): void
    {
        $violations = [];

        // Gather domain interfaces
        $domainInterfaceFiles = glob(app_path('Domain/*/Contracts/*Interface.php')) ?: [];
        $appInterfaceFiles    = glob(app_path('Application/*/Contracts/*Interface.php')) ?: [];
        $all = array_merge($domainInterfaceFiles, $appInterfaceFiles);

        foreach ($all as $path) {
            $fqcn = $this->fqcnFromPath($path);
            if (in_array($fqcn, $this->skip, true)) {
                continue;
            }

            // Derive expected provider name
            $expected = $this->expectedProviderFor($path);
            if ($expected === null) {
                // No provider expected (e.g. context without dedicated provider yet) – skip
                continue;
            }

            if (!isset($this->bindingOrigins[$fqcn])) {
                // Interface might still be container-bound via auto-resolve (not acceptable here)
                $violations[] = "$fqcn missing binding declaration in any DomainServiceProvider (expected $expected)";
                continue;
            }

            $actual = $this->bindingOrigins[$fqcn];
            if ($actual !== $expected) {
                $violations[] = "$fqcn bound in $actual but expected $expected";
            }
        }

        if (!empty($violations)) {
            $this->fail("Provider origin violations:\n".implode("\n", $violations));
        }
        $this->assertTrue(true);
    }

    /**
     * Scan all *DomainServiceProvider.php files for bind()/singleton() calls and map interface => provider FQCN.
     * @return array<string,string>
     */
    private function scanProviderBindings(): array
    {
        $map = [];
        $providerFiles = glob(app_path('Providers/*DomainServiceProvider.php')) ?: [];
        foreach ($providerFiles as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
            $providerFqcn = $this->fqcnFromPath($file);
            // Build use alias map: short => FQCN
            $aliases = [];
            foreach ($lines as $ln) {
                if (preg_match('/^use\s+(App\\\\[A-Za-z0-9_\\\\]+);/', $ln, $m)) {
                    $fq = str_replace('\\\\', '\\', $m[1]);
                    $short = Str::afterLast($fq, '\\');
                    $aliases[$short] = $fq;
                }
            }
            $buffer = '';
            $capturing = false;
            foreach ($lines as $ln) {
                if (str_contains($ln, '->bind(') || str_contains($ln, '->singleton(')) {
                    $buffer = $ln."\n";
                    $capturing = true;
                    if (str_contains($ln, ');')) {
                        // single-line call
                        $this->extractInterfaceFromBinding($buffer, $aliases, $providerFqcn, $map);
                        $buffer = '';
                        $capturing = false;
                    }
                    continue;
                }
                if ($capturing) {
                    $buffer .= $ln."\n";
                    if (str_contains($ln, ');')) {
                        $this->extractInterfaceFromBinding($buffer, $aliases, $providerFqcn, $map);
                        $buffer = '';
                        $capturing = false;
                    }
                }
            }
        }
        return $map;
    }

    /**
     * @param array<string,string> $aliases
     * @param array<string,string> $map
     */
    private function extractInterfaceFromBinding(string $bindingBlock, array $aliases, string $providerFqcn, array &$map): void
    {
        // Normalize whitespace
        $normalized = preg_replace('/\s+/', ' ', $bindingBlock) ?: $bindingBlock;
        if (preg_match('/->(?:bind|singleton)\(\s*([^,]+?),/i', $normalized, $m)) {
            $firstArg = trim($m[1]);
            // Expect pattern Something::class or 'string'
            if (preg_match('/^([A-Za-z0-9_\\\\]+)::class$/', $firstArg, $m2)) {
                $token = ltrim($m2[1], '\\');
                if (str_starts_with($token, 'App\\')) {
                    $iface = $token;
                } elseif (isset($aliases[$token])) {
                    $iface = $aliases[$token];
                } else {
                    return; // not an App FQCN we care about
                }
                $map[$iface] = $providerFqcn;
            }
        }
    }

    private function expectedProviderFor(string $path): ?string
    {
        $relative = Str::after($path, app_path().DIRECTORY_SEPARATOR);
        $segments = explode(DIRECTORY_SEPARATOR, $relative);
        if ($segments[0] === 'Domain') {
            $domain = $segments[1] ?? null;
            if (!$domain) return null;
            $providerFile = app_path('Providers/'.$domain.'DomainServiceProvider.php');
            if (!file_exists($providerFile)) return null; // no provider yet
            return 'App\\Providers\\'.$domain.'DomainServiceProvider';
        }
        if ($segments[0] === 'Application') {
            $context = $segments[1] ?? null;
            if (!$context) return null;
            $providerFile = app_path('Providers/'.$context.'DomainServiceProvider.php');
            if (!file_exists($providerFile)) return null;
            return 'App\\Providers\\'.$context.'DomainServiceProvider';
        }
        return null;
    }

    private function fqcnFromPath(string $path): string
    {
        $relative = Str::after($path, app_path().DIRECTORY_SEPARATOR);
        $withoutPhp = Str::replaceLast('.php', '', $relative);
        return 'App\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $withoutPhp);
    }
}
