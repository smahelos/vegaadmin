<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Domain\User\Enums\Permission;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class UserControllerFeatureTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsParties;

    #[Test]
    public function the_owner_lookup_returns_the_paginator_shape_select2_reads(): void
    {
        $admin = $this->backendUser(Permission::API_ACCESS, Permission::USER_VIEW);
        $marker = uniqid();
        $userFactory = User::factory();
        $owner = $userFactory->create(['name' => 'Owner ' . $marker]);

        $this->actingAs($admin, 'backpack');
        $response = $this->getJson(
            route('api.admin.users.search', ['q' => $marker]),
        );

        $response->assertOk();
        $response->assertJsonPath('current_page', 1);
        $response->assertJsonPath('last_page', 1);
        $response->assertExactJson(
            array_merge(
                $response->json(),
                [
                    'data' => [
                        [
                            'id' => $owner->id,
                            'name' => $owner->name,
                            'email' => $owner->email,
                        ],
                    ],
                ],
            ),
        );
    }

    #[Test]
    public function the_owner_lookup_needs_user_view(): void
    {
        $admin = $this->backendUser(Permission::API_ACCESS);

        $this->actingAs($admin, 'backpack');
        $response = $this->getJson(
            route('api.admin.users.search'),
        );

        $response->assertForbidden();
    }

    #[Test]
    public function the_owner_lookup_is_closed_to_guests_and_frontend_users(): void
    {
        $response = $this->getJson(
            route('api.admin.users.search'),
        );
        $response->assertUnauthorized();

        $this->actingAs($this->frontendUser(), 'web');
        $response = $this->getJson(
            route('api.admin.users.search'),
        );
        $response->assertUnauthorized();
    }
}
