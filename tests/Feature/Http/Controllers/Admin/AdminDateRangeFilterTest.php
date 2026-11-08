<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class AdminDateRangeFilterTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    public static function dateRangeFilters(): array
    {
        return [
            'invoice tax point date' => ['invoice', 'tax_point_date'],
            'expense date' => ['expense', 'expense_date'],
        ];
    }

    #[Test]
    #[DataProvider('dateRangeFilters')]
    public function list_renders_the_date_range_filter(string $route, string $filter): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->get('/admin/' . $route);

        $response->assertOk();
        $response->assertSee('data-datepicker-range-filter="' . $filter . '"', false);
        // the filters navbar only wires a filter that names its init function
        $response->assertSee('filter-init-function="billexInitDatepickerRangeFilter"', false);
    }

    #[Test]
    #[DataProvider('dateRangeFilters')]
    public function search_returns_only_records_within_the_range(string $route, string $filter): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $this->createRecord(
            $route,
            $filter,
            '2026-03-15',
        );
        $this->createRecord(
            $route,
            $filter,
            '2026-05-20',
        );

        $range = json_encode(
            ['from' => '2026-03-01 00:00:00', 'to' => '2026-03-31 23:59:59'],
        );

        $httpResponse = $this->postJson(
            "/admin/{$route}/search?{$filter}=" . urlencode($range),
            [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ],
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonPath('recordsTotal', 2);
        $httpResponse->assertJsonPath('recordsFiltered', 1);
    }

    private function createRecord(
        string $route,
        string $filter,
        string $date,
    ): Model {
        $invoiceFactory = Invoice::factory();
        $expenseFactory = Expense::factory();

        return match ($route) {
            'invoice' => $invoiceFactory->create([$filter => $date]),
            'expense' => $expenseFactory->create([$filter => $date]),
        };
    }
}
