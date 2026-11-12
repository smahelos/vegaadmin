<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\User\Enums\Permission;
use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait CreatesFrontendTestEnvironment
{
    protected User $adminUser;

    protected User $backendAdminUser;

    protected User $unauthorizedAdmin;

    protected User $user;

    /**
     * Set up frontend test environment with all required permissions and roles
     */
    protected function setUpFrontendTestEnvironment(): void
    {
        // Ensure admin role exists
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // Ensure admin role exists
        $backendAdminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'backpack',
        ]);

        // Create unauthorized admin role (without entity view permissions)
        $unauthorizedRole = Role::firstOrCreate(
            [
                'name' => 'unauthorized_admin',
                'guard_name' => 'backpack',
            ],
        );

        // Ensure frontend_user role exists
        $frontendUserRole = Role::firstOrCreate([
            'name' => 'frontend_user',
            'guard_name' => 'web',
        ]);

        // Give admin role all permissions
        $permissionQuery = PermissionModel::where('guard_name', PermissionCatalog::WEB);
        $permissionQuery->whereIn('name', PermissionCatalog::PERMISSIONS[PermissionCatalog::WEB]);
        $frontendPermissions = $permissionQuery->get();
        $adminRole->syncPermissions($frontendPermissions);

        // Create admin user with admin role (if not already set)
        $userFactory = User::factory();
        if (!isset($this->adminUser)) {
            $this->adminUser = $userFactory->create(
                [
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                ],
            );
        }
        $this->adminUser->assignRole($adminRole);

        // Give admin role all permissions
        $permissionQuery = PermissionModel::where('guard_name', PermissionCatalog::BACKPACK);
        $permissionQuery->whereIn('name', PermissionCatalog::PERMISSIONS[PermissionCatalog::BACKPACK]);
        $backendPermissions = $permissionQuery->get();
        $backendAdminRole->syncPermissions($backendPermissions);

        // Create admin user with admin role (if not already set)
        if (!isset($this->backendAdminUser)) {
            $this->backendAdminUser = $userFactory->create(
                [
                    'name' => 'Backend Admin User',
                    'email' => 'backend.admin@example.com',
                ],
            );
        }
        $this->backendAdminUser->assignRole($backendAdminRole);

        // Give unauthorized admin role basic permissions
        $permissionQuery = PermissionModel::where('guard_name', 'backpack');
        $permissionQuery->whereIn('name', [Permission::BACKPACK_ACCESS, Permission::API_ACCESS]);
        $basicBackpackPermissions = $permissionQuery->get();
        $unauthorizedRole->syncPermissions($basicBackpackPermissions);

        // Create unauthorized admin user with admin role (if not already set)
        if (!isset($this->unauthorizedAdmin)) {
            $this->unauthorizedAdmin = $userFactory->create(
                [
                    'name' => 'Unauthorized Admin',
                    'email' => 'unauthorized@example.com',
                ],
            );
        }
        $this->unauthorizedAdmin->assignRole($unauthorizedRole);

        // Create regular user with limited role (if not already set)
        if (!isset($this->user)) {
            $this->user = $userFactory->create();
        }
        $this->user->assignRole($frontendUserRole);
        // Give frontend_user role minimal API permissions, the party reads of the invoice form and the own profile
        $permissionQuery = PermissionModel::where('guard_name', 'web');
        $permissionQuery->whereIn(
            'name',
            [
                Permission::API_ACCESS,
                Permission::INVOICE_CREATE,
                Permission::INVOICE_UPDATE,
                Permission::BUSINESS_VIEW,
                Permission::CONTACT_VIEW,
                Permission::BANK_ACCOUNT_VIEW,
                Permission::PROFILE_UPDATE,
            ],
        );
        $frontendUserRole->syncPermissions(
            $permissionQuery->get(),
        );

        // Get frontend.api.access permission if it exists
        $permissionQuery = PermissionModel::where('name', Permission::API_ACCESS->value);
        $permissionQuery->where('guard_name', 'web');
        $frontendApiAccessPermission = $permissionQuery->first();
        if ($frontendApiAccessPermission) {
            $this->user->givePermissionTo($frontendApiAccessPermission);
        }
    }
}
