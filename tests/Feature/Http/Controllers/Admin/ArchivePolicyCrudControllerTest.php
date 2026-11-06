<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\ArchivePolicy;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ArchivePolicyCrudControllerTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function locales(): array
    {
        return [
            'cs' => ['cs', '24 měsíců'],
            'de' => ['de', '24 Monate'],
            'en' => ['en', '24 months'],
            'sk' => ['sk', '24 mesiacov'],
        ];
    }

    #[Test]
    #[DataProvider('locales')]
    public function list_shows_the_retention_with_a_translated_unit(string $locale, string $expected): void
    {
        $userFactory = User::factory();
        $admin = $userFactory->create();
        $admin->assignRole(
            Role::findByName('admin', 'backpack'),
        );
        ArchivePolicy::create(
            [
                'table_name' => 'translated_unit_probe',
                'retention_months' => 24,
                'date_column' => 'created_at',
            ],
        );
        $this->actingAs($admin, 'backpack');
        $this->withSession(['backpack.language-switcher.locale' => $locale]);

        $response = $this->postJson('/admin/archive-policy/search', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
        ]);

        $response->assertOk();
        $this->assertStringContainsString(
            $expected,
            (string) json_encode(
                $response->json('data'),
                JSON_UNESCAPED_UNICODE,
            ),
        );
    }
}
