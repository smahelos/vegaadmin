<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\CronTask;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Subscription;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class AdminDatepickerFieldTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    public static function datepickerFields(): array
    {
        return [
            'date' => [
                'expense',
                Expense::class,
                'expense_date',
                '2026-03-15',
            ],
            'invoice date' => [
                'invoice',
                Invoice::class,
                'issue_date',
                '2026-03-15',
            ],
            'date and time' => [
                'subscription',
                Subscription::class,
                'starts_at',
                '2026-03-15 10:20',
            ],
            'time' => [
                'cron-task',
                CronTask::class,
                'run_at',
                '10:20',
            ],
        ];
    }

    #[Test]
    #[DataProvider('datepickerFields')]
    public function edit_form_submits_the_stored_value_in_the_server_format(
        string $route,
        string $model,
        string $field,
        string $expected,
    ): void {
        $this->actingAs($this->adminUser, 'backpack');
        $modelFactory = $model::factory();
        $record = $modelFactory->create([$field => '2026-03-15 10:20:30']);

        $httpResponse = $this->get("/admin/{$route}/{$record->getKey()}/edit");
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee(
            'name="' . $field . '" value="' . $expected . '" data-datepicker-value',
            false,
        );
        $httpResponse->assertSee('data-init-function="bpFieldInitDatepicker"', false);
    }

    #[Test]
    public function edit_form_submits_an_empty_value_when_nothing_is_stored(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $subscriptionFactory = Subscription::factory();
        $record = $subscriptionFactory->create(['trial_ends_at' => null]);

        $httpResponse = $this->get("/admin/subscription/{$record->getKey()}/edit");
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee('name="trial_ends_at" value="" data-datepicker-value', false);
    }
}
