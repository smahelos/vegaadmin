<?php

namespace Tests\Feature\Providers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ensures each Party read/write repository interface has a container binding and resolves
 * to a class implementing that interface. Guards the incremental segregation (D-041).
 */
class PartyReadWriteRepositoryBindingsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string> */
    private array $interfaces = [
        // Align with current architecture: DTO repositories are the domain contracts
        'App\\Domain\\Party\\Contracts\\ClientDtoReadRepositoryInterface',
        'App\\Domain\\Party\\Contracts\\ClientDtoWriteRepositoryInterface',
        'App\\Domain\\Party\\Contracts\\SupplierDtoReadRepositoryInterface',
        'App\\Domain\\Party\\Contracts\\SupplierDtoWriteRepositoryInterface',
    ];

    #[Test]
    public function party_read_write_interfaces_are_bound_and_resolvable(): void
    {
        $missing = [];
        $resolutionErrors = [];

        foreach ($this->interfaces as $fqcn) {
            if (!app()->bound($fqcn)) {
                $missing[] = $fqcn;
                continue;
            }
            try {
                $instance = app()->make($fqcn);
                if (!$instance instanceof $fqcn) {
                    $resolutionErrors[] = $fqcn.' resolved to '.get_debug_type($instance).' which does not implement interface';
                }
            } catch (\Throwable $e) {
                $resolutionErrors[] = $fqcn.' threw: '.$e->getMessage();
            }
        }

        $messages = [];
        if ($missing) { $messages[] = 'Missing bindings: '.implode(', ', $missing); }
        if ($resolutionErrors) { $messages[] = 'Resolution errors: '.implode(', ', $resolutionErrors); }
        $this->assertEmpty($messages, "All Party read/write interfaces should be bound & resolvable.\n".implode("\n", $messages));
    }
}
