<?php

namespace Tests\Feature\Providers;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifies each Domain/Application interface is bound in the composition root of its bounded context (ADR D-042):
 *  - app/Domain/<Context>/**\/FooInterface.php      -> App\Providers\<Context>ServiceProvider
 *  - app/Application/<Context>/**\/FooInterface.php -> App\Providers\<Context>ServiceProvider
 * that no interface is bound in more than one provider and that every interface is bound at all.
 *
 * Binding discovery is shared with `ddd:providers:snapshot` (single parser for CI snapshot and this test),
 * which also scans nested contract directories (e.g. Application/Shared/Console/Contracts).
 */
class ContextProviderBindingOriginTest extends TestCase
{
    /**
     * Contracts intentionally resolved without a container binding.
     *
     * @var array<string, string> contract => reason
     */
    private const UNBOUND_CONTRACTS = [
        'App\\Domain\\Payment\\Contracts\\PaymentGatewayInterface' => 'several gateways, selected at runtime by GatewayRegistry',
        'App\\Domain\\Payment\\Contracts\\QrPaymentProviderInterface' => 'one provider per country, created by QrPaymentProviderFactory',
    ];

    #[Test]
    public function interfaces_are_bound_in_their_context_provider(): void
    {
        Artisan::call('ddd:providers:snapshot', ['--format' => 'json']);
        $contracts = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR)['snapshot']['contracts'];

        $violations = [];
        foreach ($contracts as $contract => $meta) {
            $expected = $this->expectedProviderFor($contract);
            if ($meta['status'] === 'missing_binding') {
                if (!isset(self::UNBOUND_CONTRACTS[$contract])) {
                    $violations[] = "$contract has no binding, expected one in $expected";
                }
                continue;
            }
            if ($meta['status'] === 'duplicate_bindings') {
                $providers = implode(', ', array_column($meta['actual'], 'provider'));
                $violations[] = "$contract bound in multiple providers ($providers), expected only $expected";
                continue;
            }
            if ($meta['status'] === 'ok' && $meta['actual']['provider'] !== $expected) {
                $violations[] = "$contract bound in {$meta['actual']['provider']} but expected $expected";
            }
        }

        $this->assertSame([], $violations, "Provider origin violations:\n".implode("\n", $violations));
    }

    private function expectedProviderFor(string $contract): string
    {
        // App\{Domain|Application}\<Context>\...
        $context = explode('\\', $contract)[2];
        return 'App\\Providers\\'.$context.'ServiceProvider';
    }
}
