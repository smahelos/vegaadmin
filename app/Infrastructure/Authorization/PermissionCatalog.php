<?php

declare(strict_types=1);

namespace App\Infrastructure\Authorization;

use App\Domain\User\Enums\Permission;

/**
 * Every permission and role the application relies on, with the default permissions of the
 * system roles. PermissionCatalogSync writes it to the database (additively). After changing
 * the catalog, add a migration that runs the sync again.
 *
 * Not in the catalog: permissions that only PermissionManager users create.
 */
final class PermissionCatalog
{
    public const BACKPACK = 'backpack';

    public const WEB = 'web';

    /** @var array<string, list<Permission>> permissions by guard */
    public const PERMISSIONS = [
        self::BACKPACK => [
            Permission::BACKPACK_ACCESS,
            Permission::API_ACCESS,
            Permission::SYSTEM_CONFIGURE,
            Permission::CRON_TASK_RUN,
            Permission::EXPENSE_CREATE,
            Permission::EXPENSE_UPDATE,
            Permission::EXPENSE_DELETE,
            Permission::EXPENSE_VIEW,
            Permission::BANK_CREATE,
            Permission::BUSINESS_CREATE,
            Permission::CONTACT_CREATE,
            Permission::CRON_TASK_CREATE,
            Permission::INVOICE_CREATE,
            Permission::PAGE_CREATE,
            Permission::PAYMENT_METHOD_CREATE,
            Permission::PERMISSION_CREATE,
            Permission::PRODUCT_CREATE,
            Permission::ROLE_CREATE,
            Permission::ROLE_LIMIT_CREATE,
            Permission::STATUS_CREATE,
            Permission::SUBSCRIPTION_CREATE,
            Permission::SUBSCRIPTION_PLAN_CREATE,
            Permission::SUBSCRIPTION_PLAN_FEATURE_CREATE,
            Permission::TAX_CREATE,
            Permission::USER_CREATE,
            Permission::BANK_UPDATE,
            Permission::BANK_ACCOUNT_UPDATE,
            Permission::BUSINESS_UPDATE,
            Permission::CONTACT_UPDATE,
            Permission::CRON_TASK_UPDATE,
            Permission::INVOICE_UPDATE,
            Permission::PAGE_UPDATE,
            Permission::PAYMENT_METHOD_UPDATE,
            Permission::PERMISSION_UPDATE,
            Permission::PRODUCT_UPDATE,
            Permission::ROLE_UPDATE,
            Permission::ROLE_LIMIT_UPDATE,
            Permission::STATUS_UPDATE,
            Permission::SUBSCRIPTION_UPDATE,
            Permission::SUBSCRIPTION_PLAN_UPDATE,
            Permission::SUBSCRIPTION_PLAN_FEATURE_UPDATE,
            Permission::TAX_UPDATE,
            Permission::USER_UPDATE,
            Permission::BANK_DELETE,
            Permission::CRON_TASK_DELETE,
            Permission::INVOICE_DELETE,
            Permission::PAGE_DELETE,
            Permission::PAYMENT_METHOD_DELETE,
            Permission::PERMISSION_DELETE,
            Permission::PRODUCT_DELETE,
            Permission::ROLE_DELETE,
            Permission::ROLE_LIMIT_DELETE,
            Permission::STATUS_DELETE,
            Permission::SUBSCRIPTION_DELETE,
            Permission::SUBSCRIPTION_PLAN_DELETE,
            Permission::SUBSCRIPTION_PLAN_FEATURE_DELETE,
            Permission::TAX_DELETE,
            Permission::USER_DELETE,
            Permission::BUSINESS_ARCHIVE,
            Permission::CONTACT_ARCHIVE,
            Permission::BUSINESS_RESTORE,
            Permission::CONTACT_RESTORE,
            Permission::BUSINESS_SET_DEFAULT,
            Permission::BANK_VIEW,
            Permission::BANK_ACCOUNT_VIEW,
            Permission::BUSINESS_VIEW,
            Permission::CONTACT_VIEW,
            Permission::CRON_TASK_VIEW,
            Permission::INVOICE_VIEW,
            Permission::PAGE_VIEW,
            Permission::PAYMENT_METHOD_VIEW,
            Permission::PERMISSION_VIEW,
            Permission::PRODUCT_VIEW,
            Permission::ROLE_VIEW,
            Permission::ROLE_LIMIT_VIEW,
            Permission::STATUS_VIEW,
            Permission::SUBSCRIPTION_VIEW,
            Permission::SUBSCRIPTION_PLAN_VIEW,
            Permission::SUBSCRIPTION_PLAN_FEATURE_VIEW,
            Permission::TAX_VIEW,
            Permission::USER_VIEW,
        ],
        self::WEB => [
            Permission::API_ACCESS,
            Permission::BUSINESS_ARCHIVE,
            Permission::CONTACT_ARCHIVE,
            Permission::SUBSCRIPTION_CANCEL,
            Permission::BUSINESS_CREATE,
            Permission::CONTACT_CREATE,
            Permission::INVOICE_CREATE,
            Permission::PRODUCT_CREATE,
            Permission::SUBSCRIPTION_CREATE,
            Permission::INVOICE_DELETE,
            Permission::PRODUCT_DELETE,
            Permission::BUSINESS_RESTORE,
            Permission::CONTACT_RESTORE,
            Permission::BUSINESS_SET_DEFAULT,
            Permission::BANK_ACCOUNT_UPDATE,
            Permission::BUSINESS_UPDATE,
            Permission::CONTACT_UPDATE,
            Permission::INVOICE_UPDATE,
            Permission::PRODUCT_UPDATE,
            Permission::BANK_ACCOUNT_VIEW,
            Permission::BUSINESS_VIEW,
            Permission::CONTACT_VIEW,
            Permission::INVOICE_VIEW,
            Permission::PRODUCT_VIEW,
            Permission::STATISTICS_VIEW,
            Permission::SUBSCRIPTION_VIEW,
        ],
    ];

    /** Role holding every backpack permission of the catalog */
    public const ADMIN_ROLE = 'admin';

    /**
     * System roles by guard with their default permissions. backend_user gets its permissions
     * only through PermissionManager; the frontend roles are assigned to users by the application.
     *
     * @var array<string, array<string, list<Permission>>>
     */
    public const ROLES = [
        self::BACKPACK => [
            'backend_user' => [],
        ],
        self::WEB => [
            'frontend_user' => [
                Permission::API_ACCESS,
                Permission::BUSINESS_ARCHIVE,
                Permission::CONTACT_ARCHIVE,
                Permission::SUBSCRIPTION_CANCEL,
                Permission::BUSINESS_CREATE,
                Permission::CONTACT_CREATE,
                Permission::INVOICE_CREATE,
                Permission::PRODUCT_CREATE,
                Permission::SUBSCRIPTION_CREATE,
                Permission::PRODUCT_DELETE,
                Permission::BUSINESS_RESTORE,
                Permission::CONTACT_RESTORE,
                Permission::BUSINESS_SET_DEFAULT,
                Permission::BANK_ACCOUNT_UPDATE,
                Permission::BUSINESS_UPDATE,
                Permission::CONTACT_UPDATE,
                Permission::INVOICE_UPDATE,
                Permission::PRODUCT_UPDATE,
                Permission::BANK_ACCOUNT_VIEW,
                Permission::BUSINESS_VIEW,
                Permission::CONTACT_VIEW,
                Permission::INVOICE_VIEW,
                Permission::PRODUCT_VIEW,
                Permission::STATISTICS_VIEW,
                Permission::SUBSCRIPTION_VIEW,
            ],
            'frontend_user_plus' => [
                Permission::API_ACCESS,
                Permission::BUSINESS_ARCHIVE,
                Permission::CONTACT_ARCHIVE,
                Permission::SUBSCRIPTION_CANCEL,
                Permission::BUSINESS_CREATE,
                Permission::CONTACT_CREATE,
                Permission::INVOICE_CREATE,
                Permission::PRODUCT_CREATE,
                Permission::SUBSCRIPTION_CREATE,
                Permission::INVOICE_DELETE,
                Permission::PRODUCT_DELETE,
                Permission::BUSINESS_RESTORE,
                Permission::CONTACT_RESTORE,
                Permission::BUSINESS_SET_DEFAULT,
                Permission::BANK_ACCOUNT_UPDATE,
                Permission::BUSINESS_UPDATE,
                Permission::CONTACT_UPDATE,
                Permission::INVOICE_UPDATE,
                Permission::PRODUCT_UPDATE,
                Permission::BANK_ACCOUNT_VIEW,
                Permission::BUSINESS_VIEW,
                Permission::CONTACT_VIEW,
                Permission::INVOICE_VIEW,
                Permission::PRODUCT_VIEW,
                Permission::STATISTICS_VIEW,
                Permission::SUBSCRIPTION_VIEW,
            ],
        ],
    ];

    public static function has(Permission $permission, string $guard): bool
    {
        return in_array(
            $permission,
            self::PERMISSIONS[$guard] ?? [],
            true,
        );
    }
}
