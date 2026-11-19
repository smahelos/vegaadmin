<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use App\Domain\User\Enums\Permission;
use App\Infrastructure\Authorization\PermissionCatalog;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * A permission missing in the catalog does not exist on a fresh installation, so a check of it always fails; a name
 * written as a string escapes the enum and with it every rename and removal.
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

    #[Test]
    public function code_never_writes_a_permission_name_as_a_string(): void
    {
        $values = array_map(
            fn (Permission $case) => preg_quote($case->value, '/'),
            Permission::cases(),
        );
        $pattern = '/[\'"](' . implode('|', $values) . '|(?:frontend\.)?can_[a-z_]+|(?:backpack|frontend)\.api\.[a-z_]+)[\'"]/';

        $found = [];
        foreach ($this->sources() as $path => $code) {
            if ($path === 'app/Domain/User/Enums/Permission.php') {
                continue;
            }
            preg_match_all(
                $pattern,
                $code,
                $matches,
            );
            foreach ($matches[1] as $name) {
                $found[] = "{$name} ({$path})";
            }
        }

        $this->assertSame(
            [],
            array_values(
                array_unique($found),
            ),
        );
    }

    /**
     * @return array<string, string> code by path from the project root
     */
    private function sources(): array
    {
        $finder = new Finder;
        $finder->files();
        $finder->in(
            [
                app_path(),
                base_path('routes'),
                resource_path('views'),
            ],
        );
        $finder->name('*.php');

        $sources = [];
        $root = base_path() . '/';
        foreach ($finder as $file) {
            $path = str_replace(
                $root,
                '',
                $file->getPathname(),
            );
            $sources[$path] = $file->getContents();
        }

        return $sources;
    }
}
