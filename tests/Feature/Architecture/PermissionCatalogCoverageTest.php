<?php

namespace Tests\Feature\Architecture;

use App\Http\Requests\Admin\BaseEntityRequest as AdminBaseEntityRequest;
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
     * Checked only in request classes no route uses (frontend TaxRequest, StatusRequest,
     * StatusCategoryRequest, SubscriptionPlanFeatureRequest) and in the non-profile branch of
     * UserRequest, which no route reaches.
     */
    private const UNREACHABLE = [
        'frontend.can_create_edit_tax',
        'frontend.can_create_edit_status',
        'frontend.can_create_edit_subscription_plan_feature',
        'frontend.can_create_edit_user',
    ];

    #[Test]
    public function permissions_checked_in_code_are_in_the_catalog(): void
    {
        $patterns = [
            // can('x'), hasPermissionTo('x', ...), hasBackpackPermission($id, 'x'), ...
            '/\b(?:can|hasPermissionTo|hasBackpackPermission|backpackUserHasPermission|checkPermissionTo)\(\s*(?:\(int\)\s*\$[\w>-]+\s*,\s*)?[\'"]((?:can_|backpack\.|frontend\.)[a-z_.]+)[\'"]/',
            // form requests: getRequiredPermission() { return 'x'; }
            '/function getRequiredPermission\(\): string\s*\{\s*return [\'"]([a-z_.]+)[\'"]/',
        ];

        $missing = [];
        foreach ((new Finder())->files()->in([app_path(), base_path('routes')])->name('*.php') as $file) {
            $code = $file->getContents();
            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $code, $matches);
                foreach ($matches[1] as $permission) {
                    $guard = str_starts_with($permission, 'frontend.') ? PermissionCatalog::WEB : PermissionCatalog::BACKPACK;
                    if (! PermissionCatalog::has($permission, $guard) && ! in_array($permission, self::UNREACHABLE, true)) {
                        $missing[] = "{$guard}:{$permission} (" . $file->getRelativePathname() . ')';
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'Permissions missing in PermissionCatalog');
    }

    #[Test]
    public function admin_limit_bypass_permissions_are_in_the_catalog(): void
    {
        $missing = [];
        foreach ((new Finder())->files()->in(app_path('Http/Requests/Admin'))->name('*.php') as $file) {
            $class = 'App\\Http\\Requests\\Admin\\' . $file->getBasename('.php');
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(AdminBaseEntityRequest::class)) {
                continue;
            }

            // Admin\BaseEntityRequest::hasUnlimitedAccess() checks can_create_{entity}_unlimited
            $entityType = $reflection->getMethod('getEntityType')->invoke($reflection->newInstance());
            $permission = "can_create_{$entityType}_unlimited";
            if (! PermissionCatalog::has($permission, PermissionCatalog::BACKPACK)) {
                $missing[] = $permission . ' (' . $file->getFilename() . ')';
            }
        }

        $this->assertSame([], $missing, 'Limit bypass permissions missing in PermissionCatalog');
    }
}
