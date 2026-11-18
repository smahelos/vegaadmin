<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use App\Domain\User\Enums\Permission;
use App\Infrastructure\Authorization\PermissionCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A permission missing in the catalog does not exist on a fresh installation, so a check of it always fails.
 */
final class PermissionCatalogCoverageTest extends TestCase
{
    #[Test]
    public function the_catalog_lists_every_permission_case_and_nothing_else(): void
    {
        $catalog = [];
        foreach (PermissionCatalog::PERMISSIONS as $cases) {
            foreach ($cases as $case) {
                $catalog[$case->value] = $case->value;
            }
        }
        sort($catalog);
        $values = array_column(Permission::cases(), 'value');
        sort($values);

        $this->assertSame(
            $values,
            array_values($catalog),
        );
    }
}
