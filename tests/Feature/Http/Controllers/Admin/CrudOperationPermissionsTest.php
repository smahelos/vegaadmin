<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Each admin CRUD opens only with its own permission: a backend user with admin access alone sees nothing, the
 * view permission opens the list but no writes. A category screen uses the permissions of its parent entity.
 */
final class CrudOperationPermissionsTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function screens(): array
    {
        return [
            'archive policy' => ['archive-policy', 'can_configure_system'],
            'bank' => ['bank', 'can_view_bank'],
            'expense' => ['expense', 'can_view_expense'],
            'expense category' => ['expense-category', 'can_view_expense'],
            'invoice' => ['invoice', 'can_view_invoice'],
            'page' => ['page', 'can_view_page'],
            'page category' => ['page-category', 'can_view_page'],
            'payment method' => ['payment-method', 'can_view_payment_method'],
            'permission' => ['permission', 'can_view_permission'],
            'product' => ['product', 'can_view_product'],
            'product category' => ['product-category', 'can_view_product'],
            'role' => ['role', 'can_view_role'],
            'role limit' => ['role-limit', 'can_view_role_limit'],
            'status' => ['status', 'can_view_status'],
            'status category' => ['status-category', 'can_view_status'],
            'subscription' => ['subscription', 'can_view_subscription'],
            'subscription plan' => ['subscription-plan', 'can_view_subscription_plan'],
            'subscription plan feature' => ['subscription-plan-feature', 'can_view_subscription_plan_feature'],
            'tax' => ['tax', 'can_view_tax'],
            'user' => ['user', 'can_view_user'],
        ];
    }

    /**
     * Screens whose view permission allows nothing else, with the model of a record to delete.
     *
     * @return array<string, array{string, string, class-string<Model>}>
     */
    public static function viewOnlyScreens(): array
    {
        return [
            'bank' => [
                'bank',
                'can_view_bank',
                Bank::class,
            ],
            'expense' => [
                'expense',
                'can_view_expense',
                Expense::class,
            ],
            'expense category' => [
                'expense-category',
                'can_view_expense',
                ExpenseCategory::class,
            ],
            'invoice' => [
                'invoice',
                'can_view_invoice',
                Invoice::class,
            ],
            'page' => [
                'page',
                'can_view_page',
                Page::class,
            ],
            'page category' => [
                'page-category',
                'can_view_page',
                PageCategory::class,
            ],
            'payment method' => [
                'payment-method',
                'can_view_payment_method',
                PaymentMethod::class,
            ],
            'permission' => [
                'permission',
                'can_view_permission',
                Permission::class,
            ],
            'product' => [
                'product',
                'can_view_product',
                Product::class,
            ],
            'product category' => [
                'product-category',
                'can_view_product',
                ProductCategory::class,
            ],
            'role' => [
                'role',
                'can_view_role',
                Role::class,
            ],
            'status' => [
                'status',
                'can_view_status',
                Status::class,
            ],
            'status category' => [
                'status-category',
                'can_view_status',
                StatusCategory::class,
            ],
            'subscription' => [
                'subscription',
                'can_view_subscription',
                Subscription::class,
            ],
            'subscription plan' => [
                'subscription-plan',
                'can_view_subscription_plan',
                SubscriptionPlan::class,
            ],
            'subscription plan feature' => [
                'subscription-plan-feature',
                'can_view_subscription_plan_feature',
                SubscriptionPlanFeature::class,
            ],
            'tax' => [
                'tax',
                'can_view_tax',
                Tax::class,
            ],
            'user' => [
                'user',
                'can_view_user',
                User::class,
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
    public function the_list_opens_with_the_view_permission(string $route, string $permission): void
    {
        $user = $this->backendUser([$permission]);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/' . $route);

        $response->assertOk();
    }

    #[Test]
    #[DataProvider('viewOnlyScreens')]
    public function the_create_form_is_denied_with_the_view_permission_only(string $route, string $permission): void
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
        string $permission,
        string $model,
    ): void {
        $record = $this->record($model);
        $user = $this->backendUser([$permission]);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->delete('/admin/' . $route . '/' . $record->getKey());

        $response->assertForbidden();
        $this->assertModelExists($record);
    }

    #[Test]
    public function the_archive_policies_open_with_the_system_permission(): void
    {
        $user = $this->backendUser(['can_configure_system']);

        $httpClient = $this->actingAs($user, 'backpack');
        $response = $httpClient->get('/admin/archive-policy/create');

        $response->assertOk();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function backendUser(array $permissions): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole(
            Role::findByName('backend_user', 'backpack'),
        );
        foreach (['backpack.access', ...$permissions] as $permission) {
            $user->givePermissionTo(
                Permission::findByName($permission, 'backpack'),
            );
        }

        return $user;
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

        if ($model === Role::class || $model === Permission::class) {
            return $model::create(
                ['name' => uniqid('screen_'), 'guard_name' => 'web'],
            );
        }

        $factory = $model::factory();

        return $factory->create();
    }
}
