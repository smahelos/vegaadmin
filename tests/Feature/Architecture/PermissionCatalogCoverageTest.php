<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use App\Domain\User\Enums\Permission;
use App\Infrastructure\Authorization\PermissionCatalog;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * A permission missing in the catalog of a guard does not exist on a fresh installation, so a check of it always
 * fails; a name written as a string escapes both checks below.
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
    public function code_checks_a_permission_only_in_a_guard_that_holds_it(): void
    {
        $missing = [];
        foreach ($this->sources() as $path => $code) {
            preg_match_all(
                '/(hasBackpackPermission\([^,]+,\s*)?(?<![\w\\\\])Permission::([A-Z_]+)\b(?!\s*\()(?:\s*(?:->value)?\s*,\s*[\'"](backpack|web)[\'"])?/',
                $code,
                $matches,
                PREG_SET_ORDER,
            );
            foreach ($matches as $match) {
                $guard = $this->guardOf(
                    $path,
                    $match[1] !== '',
                    $match[3] ?? '',
                );
                $permission = constant(Permission::class . '::' . $match[2]);
                if ($guard !== null && !PermissionCatalog::has($permission, $guard)) {
                    $missing[] = "{$guard}:{$permission->value} ({$path})";
                }
            }
        }

        $this->assertSame(
            [],
            array_values(
                array_unique($missing),
            ),
            'Permissions checked in a guard whose catalog lacks them',
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
     * The guard a check runs in: named in the call, the backpack adapter, or the admin or frontend code it is in.
     * Application and Infrastructure code serves both, so its checks name the guard or are not checked here.
     */
    private function guardOf(
        string $path,
        bool $backpackAdapter,
        string $named,
    ): ?string {
        if ($named !== '') {
            return $named;
        }
        if ($backpackAdapter || preg_match('#/Admin/|^resources/views/(admin|vendor/backpack)/#', $path) === 1) {
            return PermissionCatalog::BACKPACK;
        }
        if (
            preg_match(
                '#^(app/Http/|app/Livewire/|app/View/|resources/views/|routes/)#',
                $path,
            ) === 1
        ) {
            return PermissionCatalog::WEB;
        }

        return null;
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
