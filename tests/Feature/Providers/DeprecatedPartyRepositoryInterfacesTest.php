<?php

namespace Tests\Feature\Providers;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guard test ensuring deprecated Party repository interfaces remain only until planned removal.
 * Once removal phase (Krok 4 D-041) starts, switch EXPECT_DEPRECATED_EXISTS to false
 * and delete the interfaces + adjust this test accordingly.
 */
class DeprecatedPartyRepositoryInterfacesTest extends TestCase
{
    /**
     * Set to false when deprecated interfaces are removed (Krok 4 D-041).
     */
    private const EXPECT_DEPRECATED_EXISTS = false; // flip to false after removal window

    private const CLIENT_PATH = 'app/Domain/Party/Contracts/ClientRepositoryInterface.php';
    private const SUPPLIER_PATH = 'app/Domain/Party/Contracts/SupplierRepositoryInterface.php';

    #[Test]
    public function deprecated_interfaces_presence_and_annotation_aligned_with_plan(): void
    {
        $fs = new Filesystem();

        $clientExists = $fs->exists(base_path(self::CLIENT_PATH));
        $supplierExists = $fs->exists(base_path(self::SUPPLIER_PATH));

        if (self::EXPECT_DEPRECATED_EXISTS) {
            $this->assertTrue($clientExists, 'ClientRepositoryInterface should still exist until Krok 4 removal.');
            $this->assertTrue($supplierExists, 'SupplierRepositoryInterface should still exist until Krok 4 removal.');

            $client = $fs->get(base_path(self::CLIENT_PATH));
            $supplier = $fs->get(base_path(self::SUPPLIER_PATH));

            $this->assertStringContainsString('@deprecated', $client, 'ClientRepositoryInterface must be annotated @deprecated.');
            $this->assertStringContainsString('@deprecated', $supplier, 'SupplierRepositoryInterface must be annotated @deprecated.');
        } else {
            $this->assertFalse($clientExists, 'ClientRepositoryInterface should have been removed after deprecation window.');
            $this->assertFalse($supplierExists, 'SupplierRepositoryInterface should have been removed after deprecation window.');
        }
    }
}
