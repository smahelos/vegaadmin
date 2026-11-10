<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * New name of each permission by guard. Renaming the row keeps every grant, also where a role no longer
     * holds a catalog default, so the catalog is not synced here.
     *
     * @var array<string, array<string, string>>
     */
    public const RENAMES = [
        'backpack' => [
            'backpack.api.access' => 'api.access',
            'can_create_bank' => 'bank.create',
            'can_delete_bank' => 'bank.delete',
            'can_update_bank' => 'bank.update',
            'can_view_bank' => 'bank.view',
            'can_archive_business' => 'business.archive',
            'can_create_business' => 'business.create',
            'can_restore_business' => 'business.restore',
            'can_set_default_business' => 'business.set_default',
            'can_update_business' => 'business.update',
            'can_view_business' => 'business.view',
            'can_archive_contact' => 'contact.archive',
            'can_create_contact' => 'contact.create',
            'can_restore_contact' => 'contact.restore',
            'can_update_contact' => 'contact.update',
            'can_view_contact' => 'contact.view',
            'can_create_cron_task' => 'cron_task.create',
            'can_delete_cron_task' => 'cron_task.delete',
            'can_run_cron_task' => 'cron_task.run',
            'can_update_cron_task' => 'cron_task.update',
            'can_view_cron_task' => 'cron_task.view',
            'can_create_expense' => 'expense.create',
            'can_delete_expense' => 'expense.delete',
            'can_update_expense' => 'expense.update',
            'can_view_expense' => 'expense.view',
            'can_create_invoice' => 'invoice.create',
            'can_delete_invoice' => 'invoice.delete',
            'can_update_invoice' => 'invoice.update',
            'can_view_invoice' => 'invoice.view',
            'can_create_page' => 'page.create',
            'can_delete_page' => 'page.delete',
            'can_update_page' => 'page.update',
            'can_view_page' => 'page.view',
            'can_create_payment_method' => 'payment_method.create',
            'can_delete_payment_method' => 'payment_method.delete',
            'can_update_payment_method' => 'payment_method.update',
            'can_view_payment_method' => 'payment_method.view',
            'can_create_permission' => 'permission.create',
            'can_delete_permission' => 'permission.delete',
            'can_update_permission' => 'permission.update',
            'can_view_permission' => 'permission.view',
            'can_create_product' => 'product.create',
            'can_delete_product' => 'product.delete',
            'can_update_product' => 'product.update',
            'can_view_product' => 'product.view',
            'can_create_role' => 'role.create',
            'can_delete_role' => 'role.delete',
            'can_update_role' => 'role.update',
            'can_view_role' => 'role.view',
            'can_create_role_limit' => 'role_limit.create',
            'can_delete_role_limit' => 'role_limit.delete',
            'can_update_role_limit' => 'role_limit.update',
            'can_view_role_limit' => 'role_limit.view',
            'can_create_status' => 'status.create',
            'can_delete_status' => 'status.delete',
            'can_update_status' => 'status.update',
            'can_view_status' => 'status.view',
            'can_create_subscription' => 'subscription.create',
            'can_delete_subscription' => 'subscription.delete',
            'can_update_subscription' => 'subscription.update',
            'can_view_subscription' => 'subscription.view',
            'can_create_subscription_plan' => 'subscription_plan.create',
            'can_delete_subscription_plan' => 'subscription_plan.delete',
            'can_update_subscription_plan' => 'subscription_plan.update',
            'can_view_subscription_plan' => 'subscription_plan.view',
            'can_create_subscription_plan_feature' => 'subscription_plan_feature.create',
            'can_delete_subscription_plan_feature' => 'subscription_plan_feature.delete',
            'can_update_subscription_plan_feature' => 'subscription_plan_feature.update',
            'can_view_subscription_plan_feature' => 'subscription_plan_feature.view',
            'can_configure_system' => 'system.configure',
            'can_create_tax' => 'tax.create',
            'can_delete_tax' => 'tax.delete',
            'can_update_tax' => 'tax.update',
            'can_view_tax' => 'tax.view',
            'can_create_user' => 'user.create',
            'can_delete_user' => 'user.delete',
            'can_update_user' => 'user.update',
            'can_view_user' => 'user.view',
        ],
        'web' => [
            'frontend.api.access' => 'api.access',
            'frontend.can_archive_business' => 'business.archive',
            'frontend.can_create_business' => 'business.create',
            'frontend.can_restore_business' => 'business.restore',
            'frontend.can_set_default_business' => 'business.set_default',
            'frontend.can_update_business' => 'business.update',
            'frontend.can_view_business' => 'business.view',
            'frontend.can_archive_contact' => 'contact.archive',
            'frontend.can_create_contact' => 'contact.create',
            'frontend.can_restore_contact' => 'contact.restore',
            'frontend.can_update_contact' => 'contact.update',
            'frontend.can_view_contact' => 'contact.view',
            'frontend.can_create_invoice' => 'invoice.create',
            'frontend.can_delete_invoice' => 'invoice.delete',
            'frontend.can_update_invoice' => 'invoice.update',
            'frontend.can_view_invoice' => 'invoice.view',
            'frontend.can_create_product' => 'product.create',
            'frontend.can_delete_product' => 'product.delete',
            'frontend.can_update_product' => 'product.update',
            'frontend.can_view_product' => 'product.view',
            'frontend.can_view_statistics' => 'statistics.view',
            'frontend.can_cancel_subscription' => 'subscription.cancel',
            'frontend.can_create_subscription' => 'subscription.create',
            'frontend.can_view_subscription' => 'subscription.view',
        ],
    ];

    public function up(): void
    {
        $this->rename(false);
    }

    public function down(): void
    {
        $this->rename(true);
    }

    private function rename(bool $back): void
    {
        DB::transaction(
            function () use ($back): void {
                foreach (self::RENAMES as $guard => $renames) {
                    foreach ($renames as $old => $new) {
                        $from = $back ? $new : $old;
                        $to = $back ? $old : $new;
                        $permissionQuery = Permission::where('name', $from);
                        $permissionQuery->where('guard_name', $guard);
                        $permissionQuery->update(['name' => $to]);
                    }
                }
            },
        );

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
    }
};
