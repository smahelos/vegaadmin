<?php

namespace Tests\Feature\Architecture;

use App\Infrastructure\Authorization\PermissionCatalog;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Every permission the code checks by name must be in PermissionCatalog, otherwise a fresh
 * installation has no such permission and the check always fails.
 */
class PermissionCatalogCoverageTest extends TestCase
{
    /**
     * Checked only in the non-profile branch of UserRequest, which no route reaches.
     */
    private const UNREACHABLE = [
        'frontend.can_create_edit_user',
    ];

    #[Test]
    public function permissions_checked_in_code_are_in_the_catalog(): void
    {
        $patterns = [
            // can('x'), hasPermissionTo('x', ...), hasBackpackPermission($id, 'x'), ...
            '/\b(?:can|hasPermissionTo|hasBackpackPermission|backpackUserHasPermission|checkPermissionTo)\(\s*(?:\(int\)\s*\$[\w>-]+\s*,\s*)?[\'"]((?:can_|backpack\.|frontend\.)[a-z_.]+)[\'"]/',
            // form requests: getRequiredPermission() { return 'x'; }, also one return per branch
            '/function getRequiredPermission\(\): string\s*\{\s*return [\'"]([a-z_.]+)[\'"]/',
            '/\breturn [\'"]((?:can_|backpack\.|frontend\.)[a-z_.]+)[\'"];/',
            // admin CRUD controllers: denyOperationsWithout(['list' => 'x', ...])
            '/[\'"](?:list|show|create|update|delete)[\'"]\s*=>\s*[\'"]((?:can_|backpack\.)[a-z_.]+)[\'"]/',
            // any frontend permission named in code, such as the checks of Livewire components
            '/[\'"](frontend\.can_[a-z_]+)[\'"]/',
        ];

        $missing = [];
        $finder = new Finder;
        $finder->files();
        // Templates too: a permission they ask for that does not exist hides a menu entry or button for everyone
        $finder->in(
            [
                app_path(),
                base_path('routes'),
                resource_path('views'),
            ],
        );
        $finder->name('*.php');
        foreach ($finder as $file) {
            $code = $file->getContents();
            foreach ($patterns as $pattern) {
                preg_match_all(
                    $pattern,
                    $code,
                    $matches,
                );
                foreach ($matches[1] as $permission) {
                    $guard = str_starts_with($permission, 'frontend.') ? PermissionCatalog::WEB : PermissionCatalog::BACKPACK;
                    if (
                        !PermissionCatalog::has($permission, $guard)
                        && !in_array(
                            $permission,
                            self::UNREACHABLE,
                            true,
                        )
                    ) {
                        $missing[] = "{$guard}:{$permission} (" . $file->getRelativePathname() . ')';
                    }
                }
            }
        }

        $this->assertSame(
            [],
            array_values(
                array_unique($missing),
            ),
            'Permissions missing in PermissionCatalog',
        );
    }
}
