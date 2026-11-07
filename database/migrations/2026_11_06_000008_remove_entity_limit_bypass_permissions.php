<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * No permission bypasses a plan limit and admin reference data have none (D-047).
     *
     * @var list<string>
     */
    private const PERMISSIONS = [
        'can_create_bank_unlimited',
        'can_create_expense_unlimited',
        'can_create_invoice_unlimited',
        'can_create_payment_method_unlimited',
        'can_create_product_unlimited',
        'can_create_product_category_unlimited',
        'can_create_tax_unlimited',
    ];

    public function up(): void
    {
        $permissions = Permission::where('guard_name', 'backpack');
        $permissions->whereIn('name', self::PERMISSIONS);
        $permissions->delete();
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::transaction(
            function (): void {
                foreach (self::PERMISSIONS as $name) {
                    Permission::findOrCreate($name, 'backpack');
                }
                $role = Role::findOrCreate('admin', 'backpack');
                $role->givePermissionTo(self::PERMISSIONS);
            },
        );
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
    }
};
