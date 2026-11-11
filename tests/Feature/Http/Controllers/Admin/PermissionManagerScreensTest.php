<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
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
            'role form in Czech' => [
                '/admin/role/create',
                'cs',
                'Název',
            ],
            'role form in Slovak' => [
                '/admin/role/create',
                'sk',
                'Názov',
            ],
            'permission form in Czech' => [
                '/admin/permission/create',
                'cs',
                'Název',
            ],
            'permission form in Slovak' => [
                '/admin/permission/create',
                'sk',
                'Názov',
            ],
        ];
    }

    #[Test]
    #[DataProvider('screens')]
    public function screen_shows_translated_labels(
        string $url,
        string $locale,
        string $label,
    ): void {
        $this->actingAs($this->adminUser, 'backpack');

        $httpClient = $this->withSession(['backpack.language-switcher.locale' => $locale]);
        $httpResponse = $httpClient->get($url);
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSeeText($label);
        $httpResponse->assertDontSeeText('permissionmanager.');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function permissionPickers(): array
    {
        return [
            'role form' => ['/admin/role/create'],
            'user list filter' => ['/admin/user'],
            'user form' => ['/admin/user/create'],
        ];
    }

    #[Test]
    #[DataProvider('permissionPickers')]
    public function permissions_are_shown_with_their_guard(string $url): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $httpResponse = $this->get($url);
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSeeText(Permission::PRODUCT_VIEW->value . ' (backpack)');
        $httpResponse->assertSeeText(Permission::PRODUCT_VIEW->value . ' (web)');
    }

    #[Test]
    public function the_role_list_shows_permissions_with_their_guard(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $httpResponse = $this->post('/admin/role/search');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee(Permission::PRODUCT_VIEW->value . ' (backpack)');
        $httpResponse->assertSee(Permission::PRODUCT_VIEW->value . ' (web)');
    }
}
