<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

/**
 * A permission the application checks; the value is its stored name, the same in every guard that holds it.
 * PermissionCatalog assigns the cases to guards and roles.
 */
enum Permission: string
{
    case API_ACCESS = 'api.access';
    case BACKPACK_ACCESS = 'backpack.access';
    case BANK_ACCOUNT_UPDATE = 'bank_account.update';
    case BANK_ACCOUNT_VIEW = 'bank_account.view';
    case BANK_CREATE = 'bank.create';
    case BANK_DELETE = 'bank.delete';
    case BANK_UPDATE = 'bank.update';
    case BANK_VIEW = 'bank.view';
    case BUSINESS_ARCHIVE = 'business.archive';
    case BUSINESS_CREATE = 'business.create';
    case BUSINESS_RESTORE = 'business.restore';
    case BUSINESS_SET_DEFAULT = 'business.set_default';
    case BUSINESS_UPDATE = 'business.update';
    case BUSINESS_VIEW = 'business.view';
    case CONTACT_ARCHIVE = 'contact.archive';
    case CONTACT_CREATE = 'contact.create';
    case CONTACT_RESTORE = 'contact.restore';
    case CONTACT_UPDATE = 'contact.update';
    case CONTACT_VIEW = 'contact.view';
    case CRON_TASK_CREATE = 'cron_task.create';
    case CRON_TASK_DELETE = 'cron_task.delete';
    case CRON_TASK_RUN = 'cron_task.run';
    case CRON_TASK_UPDATE = 'cron_task.update';
    case CRON_TASK_VIEW = 'cron_task.view';
    case EXPENSE_CREATE = 'expense.create';
    case EXPENSE_DELETE = 'expense.delete';
    case EXPENSE_UPDATE = 'expense.update';
    case EXPENSE_VIEW = 'expense.view';
    case INVOICE_CREATE = 'invoice.create';
    case INVOICE_DELETE = 'invoice.delete';
    case INVOICE_UPDATE = 'invoice.update';
    case INVOICE_VIEW = 'invoice.view';
    case PAGE_CREATE = 'page.create';
    case PAGE_DELETE = 'page.delete';
    case PAGE_UPDATE = 'page.update';
    case PAGE_VIEW = 'page.view';
    case PAYMENT_METHOD_CREATE = 'payment_method.create';
    case PAYMENT_METHOD_DELETE = 'payment_method.delete';
    case PAYMENT_METHOD_UPDATE = 'payment_method.update';
    case PAYMENT_METHOD_VIEW = 'payment_method.view';
    case PERMISSION_CREATE = 'permission.create';
    case PERMISSION_DELETE = 'permission.delete';
    case PERMISSION_UPDATE = 'permission.update';
    case PERMISSION_VIEW = 'permission.view';
    case PRODUCT_CREATE = 'product.create';
    case PRODUCT_DELETE = 'product.delete';
    case PRODUCT_UPDATE = 'product.update';
    case PRODUCT_VIEW = 'product.view';
    case ROLE_CREATE = 'role.create';
    case ROLE_DELETE = 'role.delete';
    case ROLE_UPDATE = 'role.update';
    case ROLE_VIEW = 'role.view';
    case ROLE_LIMIT_CREATE = 'role_limit.create';
    case ROLE_LIMIT_DELETE = 'role_limit.delete';
    case ROLE_LIMIT_UPDATE = 'role_limit.update';
    case ROLE_LIMIT_VIEW = 'role_limit.view';
    case STATISTICS_VIEW = 'statistics.view';
    case STATUS_CREATE = 'status.create';
    case STATUS_DELETE = 'status.delete';
    case STATUS_UPDATE = 'status.update';
    case STATUS_VIEW = 'status.view';
    case SUBSCRIPTION_CANCEL = 'subscription.cancel';
    case SUBSCRIPTION_CREATE = 'subscription.create';
    case SUBSCRIPTION_DELETE = 'subscription.delete';
    case SUBSCRIPTION_UPDATE = 'subscription.update';
    case SUBSCRIPTION_VIEW = 'subscription.view';
    case SUBSCRIPTION_PLAN_CREATE = 'subscription_plan.create';
    case SUBSCRIPTION_PLAN_DELETE = 'subscription_plan.delete';
    case SUBSCRIPTION_PLAN_UPDATE = 'subscription_plan.update';
    case SUBSCRIPTION_PLAN_VIEW = 'subscription_plan.view';
    case SUBSCRIPTION_PLAN_FEATURE_CREATE = 'subscription_plan_feature.create';
    case SUBSCRIPTION_PLAN_FEATURE_DELETE = 'subscription_plan_feature.delete';
    case SUBSCRIPTION_PLAN_FEATURE_UPDATE = 'subscription_plan_feature.update';
    case SUBSCRIPTION_PLAN_FEATURE_VIEW = 'subscription_plan_feature.view';
    case SYSTEM_CONFIGURE = 'system.configure';
    case TAX_CREATE = 'tax.create';
    case TAX_DELETE = 'tax.delete';
    case TAX_UPDATE = 'tax.update';
    case TAX_VIEW = 'tax.view';
    case USER_CREATE = 'user.create';
    case USER_DELETE = 'user.delete';
    case USER_UPDATE = 'user.update';
    case USER_VIEW = 'user.view';
}
