<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\PageCategory;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Backpack's closure column escapes the closure's output unless the column sets escaped(false),
 * and shows its default ('-' unless set) for an empty value — 0 included.
 */
final class ClosureColumnTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $userFactory = User::factory();
        $this->admin = $userFactory->create();
        $this->admin->assignRole(
            Role::findByName(PermissionCatalog::ADMIN_ROLE, PermissionCatalog::BACKPACK),
        );
    }

    #[Test]
    public function escapes_text_and_shows_zero_count(): void
    {
        $name = '<b>Category ' . uniqid() . '</b>';
        $pageCategoryFactory = PageCategory::factory();
        $pageCategoryFactory->create(
            ['name' => array_fill_keys([
                'cs',
                'de',
                'en',
                'sk',
            ], $name)],
        );

        $row = $this->listRow(
            '/admin/page-category',
            e($name),
        );

        $this->assertStringNotContainsString($name, $row);
        $this->assertMatchesRegularExpression('/<span>\s*0\s*<\/span>/', $row);
    }

    #[Test]
    public function an_html_column_escapes_the_owner_name(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['name' => '<i>Business ' . uniqid() . '</i>']);
        $bankAccountFactory = BankAccount::factory();
        $ownedAccountFactory = $bankAccountFactory->ownedBy($business);
        $ownedAccountFactory->create();

        $row = $this->listRow(
            '/admin/bank-account',
            e($business->name),
        );

        $this->assertStringNotContainsString($business->name, $row);
    }

    /**
     * The DataTables row (rendered cells joined) of the list view that contains the given text.
     */
    private function listRow(string $route, string $needle): string
    {
        $this->actingAs($this->admin, PermissionCatalog::BACKPACK);

        $response = $this->postJson($route . '/search', [
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ]);
        $response->assertOk();

        $collection = collect(
            $response->json('data'),
        );
        $mapped = $collection->map(
            fn (array $cells) => implode('', $cells),
        );
        $row = $mapped->first(
            fn (string $cells) => str_contains($cells, $needle),
        );
        $this->assertNotNull($row, "No list row of {$route} contains {$needle}");

        return $row;
    }
}
