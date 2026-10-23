<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * The role and permission screens come from backpack/permissionmanager, which ships
 * no Czech or Slovak texts; lang/vendor/backpack provides them.
 */
final class PermissionManagerScreensTest extends TestCase
{
    use CreatesAdminTestEnvironment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function screens(): array
    {
        return [
            'role form in Czech' => ['/admin/role/create', 'cs', 'Název'],
            'role form in Slovak' => ['/admin/role/create', 'sk', 'Názov'],
            'permission form in Czech' => ['/admin/permission/create', 'cs', 'Název'],
            'permission form in Slovak' => ['/admin/permission/create', 'sk', 'Názov'],
        ];
    }

    #[Test]
    #[DataProvider('screens')]
    public function screen_shows_translated_labels(string $url, string $locale, string $label): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $this->withSession(['backpack.language-switcher.locale' => $locale])
            ->get($url)
            ->assertOk()
            ->assertSeeText($label)
            ->assertDontSeeText('permissionmanager.');
    }
}
