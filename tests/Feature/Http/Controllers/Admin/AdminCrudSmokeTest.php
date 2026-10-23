<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\ArchivePolicy;
use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use App\Models\Bank;
use App\Models\Client;
use App\Models\CronTask;
use App\Models\DatabaseMaintenanceLog;
use App\Models\EntityLimit;
use App\Models\EntityLimitUsage;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\MysqlOptimizationLog;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PaymentMethod;
use App\Models\PerformanceMetric;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Renders every operation page of every admin CRUD with one existing record,
 * so broken Backpack field, column or filter templates fail here
 * (safety net for template rewrites and Backpack upgrades).
 */
class AdminCrudSmokeTest extends TestCase
{
    use CreatesAdminTestEnvironment;
    use RefreshDatabase;

    private User $adminUser;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    public static function cruds(): array
    {
        return [
            'archive-policy' => ['archive-policy', ArchivePolicy::class],
            'artisan-command-category' => ['artisan-command-category', ArtisanCommandCategory::class],
            'artisan-command' => ['artisan-command', ArtisanCommand::class],
            'bank' => ['bank', Bank::class],
            'client' => ['client', Client::class],
            'cron-task' => ['cron-task', CronTask::class],
            'database-maintenance-log' => ['database-maintenance-log', DatabaseMaintenanceLog::class],
            'entity-limit' => ['entity-limit', EntityLimit::class],
            'entity-usage' => ['entity-usage', EntityLimitUsage::class],
            'expense-category' => ['expense-category', ExpenseCategory::class],
            'expense' => ['expense', Expense::class],
            'invoice' => ['invoice', Invoice::class],
            'mysql-optimization-log' => ['mysql-optimization-log', MysqlOptimizationLog::class],
            'page-category' => ['page-category', PageCategory::class],
            'page' => ['page', Page::class],
            'payment-method' => ['payment-method', PaymentMethod::class],
            'performance-metric' => ['performance-metric', PerformanceMetric::class],
            'permission' => ['permission', Permission::class],
            'product-category' => ['product-category', ProductCategory::class],
            'product' => ['product', Product::class],
            'role' => ['role', Role::class],
            'status-category' => ['status-category', StatusCategory::class],
            'status' => ['status', Status::class],
            'subscription-plan-feature' => ['subscription-plan-feature', SubscriptionPlanFeature::class],
            'subscription-plan' => ['subscription-plan', SubscriptionPlan::class],
            'subscription' => ['subscription', Subscription::class],
            'supplier' => ['supplier', Supplier::class],
            'tax' => ['tax', Tax::class],
            'user' => ['user', User::class],
        ];
    }

    #[Test]
    #[DataProvider('cruds')]
    public function crud_operations_render_with_a_record(string $route, string $model): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $record = $this->createRecord($model);
        $base = '/admin/' . $route;

        $this->freshCrud()->get($base)->assertOk();

        // DataTables request of the list view; renders every column of the record
        $this->freshCrud()->postJson($base . '/search', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ])
            ->assertJsonPath('recordsTotal', fn ($total) => $total >= 1);

        if ($this->hasRoute("{$base}/create")) {
            $this->freshCrud()->get($base . '/create')->assertOk();
        }
        if ($this->hasRoute("{$base}/{id}/edit")) {
            $this->freshCrud()->get("{$base}/{$record->getKey()}/edit")->assertOk();
        }
        if ($this->hasRoute("{$base}/{id}/show")) {
            $this->freshCrud()->get("{$base}/{$record->getKey()}/show")->assertOk();
        }
    }

    /**
     * Start the next request with a new CrudPanel and CRUD controller, as a real request does.
     * Consecutive test requests share the scoped 'CrudManager' holding the panel, the scoped
     * lifecycle hooks (both also cached by their facades) and the controller cached on the
     * route, so the second request would set up the panel twice (duplicate filters).
     */
    private function freshCrud(): static
    {
        foreach ([
            'CrudManager',
            'BackpackLifecycleHooks',
            'crud',
        ] as $abstract) {
            $this->app->forgetInstance($abstract);
            Facade::clearResolvedInstance($abstract);
        }
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $route->flushController();
        }

        return $this;
    }

    private function createRecord(string $model): Model
    {
        return match ($model) {
            EntityLimitUsage::class => EntityLimitUsage::create([
                'user_id' => $this->adminUser->id,
                'entity_type' => 'client',
                'metric_type' => 'count',
                'period_type' => 'monthly',
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'current_value' => 1,
            ]),
            Permission::class => Permission::create(['name' => 'smoke_' . uniqid(), 'guard_name' => 'backpack']),
            Role::class => Role::create(['name' => 'smoke_' . uniqid(), 'guard_name' => 'backpack']),
            default => $model::factory()->create(),
        };
    }

    private function hasRoute(string $uri): bool
    {
        $uri = ltrim($uri, '/');

        return collect(Route::getRoutes()->getRoutes())
            ->contains(fn ($route) => $route->uri() === $uri && in_array('GET', $route->methods(), true));
    }
}
