<?php

declare(strict_types=1);

namespace App\Infrastructure\Authorization;

/**
 * Every permission and role the application relies on, with the default permissions of the
 * system roles. PermissionCatalogSync writes it to the database (additively). After changing
 * the catalog, add a migration that runs the sync again.
 *
 * Not in the catalog: UELS limit_* permissions (plan configuration, created with their
 * entity_limits rows) and permissions that only PermissionManager users create.
 */
final class PermissionCatalog
{
    public const BACKPACK = 'backpack';

    public const WEB = 'web';

    /** @var array<string, list<string>> permission names by guard */
    public const PERMISSIONS = [
        self::BACKPACK => [
            'backpack.access',
            'backpack.api.access',
            'backpack.api.extended',
            'backpack.api.invoices',
            'backpack.api.products',
            'can_configure_system',
            'can_create_edit_bank',
            'can_create_edit_command',
            'can_create_edit_cron_task',
            'can_create_expense',
            'can_update_expense',
            'can_delete_expense',
            'can_view_expense',
            'can_create_edit_expense',
            'can_create_edit_invoice',
            'can_create_edit_page',
            'can_create_edit_payment_method',
            'can_create_edit_product',
            'can_create_edit_status',
            'can_create_edit_subscription',
            'can_create_edit_subscription_plan',
            'can_create_edit_subscription_plan_feature',
            'can_create_edit_tax',
            'can_create_edit_user',
            'can_create_business',
            'can_create_contact',
            'can_create_invoice',
            'can_create_product',
            'can_create_user',
            'can_update_business',
            'can_update_contact',
            'can_update_invoice',
            'can_update_product',
            'can_update_user',
            'can_delete_invoice',
            'can_delete_product',
            'can_delete_user',
            'can_archive_business',
            'can_archive_contact',
            'can_restore_business',
            'can_restore_contact',
            'can_set_default_business',
            'can_view_business',
            'can_view_contact',
            'can_view_invoice',
            'can_view_payment_method',
            'can_view_product',
            'can_view_user',
            // Bypass of the entity limits when creating in the admin (Admin\BaseEntityRequest)
            'can_create_bank_unlimited',
            'can_create_expense_unlimited',
            'can_create_invoice_unlimited',
            'can_create_payment_method_unlimited',
            'can_create_product_unlimited',
            'can_create_product_category_unlimited',
            'can_create_tax_unlimited',
        ],
        self::WEB => [
            'frontend.api.access',
            'frontend.api.extended',
            'frontend.api.invoices',
            'frontend.api.products',
            'frontend.api.statistics',
            'frontend.can_archive_business',
            'frontend.can_archive_contact',
            'frontend.can_cancel_subscription',
            'frontend.can_create_business',
            'frontend.can_create_contact',
            'frontend.can_create_edit_expense',
            'frontend.can_create_edit_invoice',
            'frontend.can_create_edit_product',
            'frontend.can_create_subscription',
            'frontend.can_delete_invoice',
            'frontend.can_delete_product',
            'frontend.can_restore_business',
            'frontend.can_restore_contact',
            'frontend.can_set_default_business',
            'frontend.can_update_business',
            'frontend.can_update_contact',
            'frontend.can_view_business',
            'frontend.can_view_contact',
            'frontend.can_view_invoice',
            'frontend.can_view_product',
            'frontend.can_view_subscription',
        ],
    ];

    /** Role holding every backpack permission of the catalog */
    public const ADMIN_ROLE = 'admin';

    /**
     * System roles by guard with their default permissions. backend_user gets its permissions
     * only through PermissionManager; the frontend roles are assigned to users by the application.
     *
     * @var array<string, array<string, list<string>>>
     */
    public const ROLES = [
        self::BACKPACK => [
            'backend_user' => [],
        ],
        self::WEB => [
            'frontend_user' => [
                'frontend.api.access',
                'frontend.api.invoices',
                'frontend.api.products',
                'frontend.api.statistics',
                'frontend.can_archive_business',
                'frontend.can_archive_contact',
                'frontend.can_cancel_subscription',
                'frontend.can_create_business',
                'frontend.can_create_contact',
                'frontend.can_create_edit_invoice',
                'frontend.can_create_edit_product',
                'frontend.can_create_subscription',
                'frontend.can_restore_business',
                'frontend.can_restore_contact',
                'frontend.can_set_default_business',
                'frontend.can_update_business',
                'frontend.can_update_contact',
                'frontend.can_view_business',
                'frontend.can_view_contact',
                'frontend.can_view_invoice',
                'frontend.can_view_product',
                'frontend.can_view_subscription',
            ],
            'frontend_user_plus' => [
                'frontend.api.access',
                'frontend.api.extended',
                'frontend.api.invoices',
                'frontend.api.products',
                'frontend.api.statistics',
                'frontend.can_archive_business',
                'frontend.can_archive_contact',
                'frontend.can_cancel_subscription',
                'frontend.can_create_business',
                'frontend.can_create_contact',
                'frontend.can_create_edit_expense',
                'frontend.can_create_edit_invoice',
                'frontend.can_create_edit_product',
                'frontend.can_create_subscription',
                'frontend.can_delete_invoice',
                'frontend.can_delete_product',
                'frontend.can_restore_business',
                'frontend.can_restore_contact',
                'frontend.can_set_default_business',
                'frontend.can_update_business',
                'frontend.can_update_contact',
                'frontend.can_view_business',
                'frontend.can_view_contact',
                'frontend.can_view_invoice',
                'frontend.can_view_product',
                'frontend.can_view_subscription',
            ],
        ],
    ];

    public static function has(string $permission, string $guard): bool
    {
        return in_array(
            $permission,
            self::PERMISSIONS[$guard] ?? [],
            true,
        );
    }
}
