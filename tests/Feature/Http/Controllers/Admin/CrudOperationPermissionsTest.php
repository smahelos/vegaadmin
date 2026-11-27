<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\User\Enums\Permission;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\CronTask;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PaymentMethod;
use App\Models\Permission as PermissionModel;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RoleLimit;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Each admin CRUD opens only with its own permission: a backend user with admin access alone sees nothing, the
 * view permission opens the list but no writes. A category screen uses the permissions of its parent entity.
 */
final class CrudOperationPermissionsTest extends TestCase
{
    /**
     * @return array<string, array{string, Permission}>
     */
    public static function screens(): array
    {
        return [
            'bank' => ['bank', Permission::BANK_VIEW],
            'bank account' => ['bank-account', Permission::BANK_ACCOUNT_VIEW],
            'business' => ['business', Permission::BUSINESS_VIEW],
            'contact' => ['contact', Permission::CONTACT_VIEW],
            'cron task' => ['cron-task', Permission::CRON_TASK_VIEW],
            'database maintenance log' => ['database-maintenance-log', Permission::SYSTEM_CONFIGURE],
            'expense' => ['expense', Permission::EXPENSE_VIEW],
            'expense category' => ['expense-category', Permission::EXPENSE_VIEW],
            'invoice' => ['invoice', Permission::INVOICE_VIEW],
            'mysql optimization log' => ['mysql-optimization-log', Permission::SYSTEM_CONFIGURE],
            'page' => ['page', Permission::PAGE_VIEW],
            'page category' => ['page-category', Permission::PAGE_VIEW],
            'payment method' => ['payment-method', Permission::PAYMENT_METHOD_VIEW],
            'performance metric' => ['performance-metric', Permission::SYSTEM_CONFIGURE],
            'permission' => ['permission', Permission::PERMISSION_VIEW],
            'product' => ['product', Permission::PRODUCT_VIEW],
            'product category' => ['product-category', Permission::PRODUCT_VIEW],
            'role' => ['role', Permission::ROLE_VIEW],
            'role limit' => ['role-limit', Permission::ROLE_LIMIT_VIEW],
            'status' => ['status', Permission::STATUS_VIEW],
            'status category' => ['status-category', Permission::STATUS_VIEW],
            'subscription' => ['subscription', Permission::SUBSCRIPTION_VIEW],
            'subscription plan' => ['subscription-plan', Permission::SUBSCRIPTION_PLAN_VIEW],
            'subscription plan feature' => ['subscription-plan-feature', Permission::SUBSCRIPTION_PLAN_FEATURE_VIEW],
            'tax' => ['tax', Permission::TAX_VIEW],
            'user' => ['user', Permission::USER_VIEW],
        ];
    }

    /**
     * Screens whose view permission allows nothing else, with the model of a record to delete.
     *
     * @return array<string, array{string, Permission, class-string<Model>}>
     */
    public static function viewOnlyScreens(): array
    {
        return [
            'bank' => [
                'bank',
                Permission::BANK_VIEW,
                Bank::class,
            ],
            'cron task' => [
                'cron-task',
                Permission::CRON_TASK_VIEW,
                CronTask::class,
            ],
            'expense' => [
                'expense',
                Permission::EXPENSE_VIEW,
                Expense::class,
            ],
            'expense category' => [
                'expense-category',
                Permission::EXPENSE_VIEW,
                ExpenseCategory::class,
            ],
            'page' => [
                'page',
                Permission::PAGE_VIEW,
                Page::class,
            ],
            'page category' => [
                'page-category',
                Permission::PAGE_VIEW,
                PageCategory::class,
            ],
            'payment method' => [
                'payment-method',
                Permission::PAYMENT_METHOD_VIEW,
                PaymentMethod::class,
            ],
            'permission' => [
                'permission',
                Permission::PERMISSION_VIEW,
                PermissionModel::class,
            ],
            'product' => [
                'product',
                Permission::PRODUCT_VIEW,
                Product::class,
            ],
            'product category' => [
                'product-category',
                Permission::PRODUCT_VIEW,
                ProductCategory::class,
            ],
            'role' => [
                'role',
                Permission::ROLE_VIEW,
                Role::class,
            ],
            'role limit' => [
                'role-limit',
                Permission::ROLE_LIMIT_VIEW,
                RoleLimit::class,
            ],
            'status' => [
                'status',
                Permission::STATUS_VIEW,
                Status::class,
            ],
            'status category' => [
                'status-category',
                Permission::STATUS_VIEW,
                StatusCategory::class,
            ],
            'subscription' => [
                'subscription',
                Permission::SUBSCRIPTION_VIEW,
                Subscription::class,
            ],
            'subscription plan' => [
                'subscription-plan',
                Permission::SUBSCRIPTION_PLAN_VIEW,
                SubscriptionPlan::class,
            ],
            'subscription plan feature' => [
                'subscription-plan-feature',
                Permission::SUBSCRIPTION_PLAN_FEATURE_VIEW,
                SubscriptionPlanFeature::class,
            ],
            'tax' => [
                'tax',
                Permission::TAX_VIEW,
                Tax::class,
            ],
            'user' => [
                'user',
                Permission::USER_VIEW,
                User::class,
            ],
        ];
    }

    /**
     * Screens with a create and an update operation, including those that write records with an owner directly.
     *
     * @return array<string, array{string, Permission, class-string<Model>}>
     */
    public static function writableScreens(): array
    {
        return [
            ...self::viewOnlyScreens(),
            'bank account' => [
                'bank-account',
                Permission::BANK_ACCOUNT_VIEW,
                BankAccount::class,
            ],
            'business' => [
                'business',
                Permission::BUSINESS_VIEW,
                Business::class,
            ],
            'contact' => [
                'contact',
                Permission::CONTACT_VIEW,
                Contact::class,
            ],
        ];
    }

    #[Test]
    #[DataProvider('screens')]
    public function the_list_is_denied_without_the_view_permission(string $route): void
    {
        $user = $this->backendUser([]);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/' . $route);

        $response->assertForbidden();
    }

    #[Test]
    #[DataProvider('screens')]
    public function the_list_opens_with_the_view_permission(string $route, Permission $permission): void
    {
        $user = $this->backendUser([$permission]);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/' . $route);

        $response->assertOk();
    }

    #[Test]
    #[DataProvider('viewOnlyScreens')]
    public function the_create_form_is_denied_with_the_view_permission_only(string $route, Permission $permission): void
    {
        $user = $this->backendUser([$permission]);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/' . $route . '/create');

        $response->assertForbidden();
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[Test]
    #[DataProvider('viewOnlyScreens')]
    public function deleting_is_denied_with_the_view_permission_only(
        string $route,
        Permission $permission,
        string $model,
    ): void {
        $record = $this->record($model);
        $user = $this->backendUser([$permission]);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->delete('/admin/' . $route . '/' . $record->getKey());

        $response->assertForbidden();
        $this->assertModelExists($record);
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[Test]
    #[DataProvider('writableScreens')]
    public function writing_invalid_data_is_denied_with_the_view_permission_only(
        string $route,
        Permission $permission,
        string $model,
    ): void {
        $record = $this->record($model);
        $user = $this->backendUser([$permission]);

        $httpClient = $this->actingAs($user, 'backpack');
        $storeResponse = $httpClient->post('/admin/' . $route, []);
        $updateResponse = $httpClient->put('/admin/' . $route . '/' . $record->getKey(), []);

        // The operation is refused before validation, so invalid data never reaches a 422
        $storeResponse->assertForbidden();
        $updateResponse->assertForbidden();
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[Test]
    #[DataProvider('writableScreens')]
    public function the_create_form_opens_with_the_create_permission(
        string $route,
        Permission $permission,
        string $model,
    ): void {
        $user = $this->backendUser(
            [$this->operationPermission($permission, 'create')],
        );

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/' . $route . '/create');

        $response->assertOk();
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[Test]
    #[DataProvider('writableScreens')]
    public function the_edit_form_opens_with_the_update_permission(
        string $route,
        Permission $permission,
        string $model,
    ): void {
        $record = $this->record($model);
        $user = $this->backendUser(
            [$this->operationPermission($permission, 'update')],
        );

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/' . $route . '/' . $record->getKey() . '/edit');

        $response->assertOk();
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[Test]
    #[DataProvider('viewOnlyScreens')]
    public function deleting_opens_with_the_delete_permission(
        string $route,
        Permission $permission,
        string $model,
    ): void {
        $record = $this->record($model);
        $user = $this->backendUser(
            [$this->operationPermission($permission, 'delete')],
        );

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->delete('/admin/' . $route . '/' . $record->getKey());

        $response->assertOk();
        // A query rather than assertModelMissing, which a soft-deleted user or plan feature would fail
        $recordQuery = $record->newQuery();
        $this->assertNull(
            $recordQuery->find($record->getKey()),
        );
    }

    #[Test]
    public function an_operation_the_package_configuration_denies_stays_denied_with_its_permission(): void
    {
        config(['backpack.permissionmanager.allow_role_delete' => false]);
        $record = $this->record(Role::class);
        $user = $this->backendUser([Permission::ROLE_DELETE]);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->delete('/admin/role/' . $record->getKey());

        $response->assertForbidden();
        $this->assertModelExists($record);
    }

    /**
     * @param  list<Permission>  $permissions
     */
    private function backendUser(array $permissions): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName('backend_user', 'backpack'),
        );
        foreach ([Permission::BACKPACK_ACCESS, ...$permissions] as $permission) {
            $user->givePermissionTo(
                PermissionModel::findByName($permission->value, 'backpack'),
            );
        }

        return $user;
    }

    /**
     * The permission of an operation, named after the screen's view permission (`bank.view` → `bank.create`).
     */
    private function operationPermission(Permission $view, string $operation): Permission
    {
        // Bank accounts are created under their update permission
        if ($view === Permission::BANK_ACCOUNT_VIEW && $operation === 'create') {
            return Permission::BANK_ACCOUNT_UPDATE;
        }

        return Permission::from(
            Str::beforeLast($view->value, '.') . '.' . $operation,
        );
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function record(string $model): Model
    {
        if ($model === PaymentMethod::class) {
            $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::CASH->value);

            return $paymentMethodQuery->firstOrFail();
        }

        if ($model === Role::class || $model === PermissionModel::class) {
            return $model::create(
                ['name' => uniqid('screen_'), 'guard_name' => 'web'],
            );
        }

        if ($model === RoleLimit::class) {
            $role = Role::create(
                ['name' => uniqid('screen_'), 'guard_name' => 'web'],
            );

            return RoleLimit::create(
                ['role_id' => $role->getKey()],
            );
        }

        $factory = Factory::factoryForModel($model);

        return $factory->create();
    }
}
