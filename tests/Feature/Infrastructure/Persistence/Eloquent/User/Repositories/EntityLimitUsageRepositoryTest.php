<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\User\Repositories;

use App\Domain\User\Contracts\EntityLimitUsageRepositoryInterface;
use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\EntityLimit;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EntityLimitUsageRepositoryTest extends TestCase
{
    #[Test]
    public function limit_on_a_permission_of_the_other_guard_does_not_break_the_permission_check(): void
    {
        // can_create_edit_invoice exists only for the backpack guard; the reference data limits
        // invoices through limit_*_basic, which frontend_user holds on the web guard
        EntityLimit::factory()->create([
            'entity_type' => 'invoice',
            'permission_name' => 'can_create_edit_invoice',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $user->assignRole('frontend_user');

        $repository = $this->app->make(EntityLimitUsageRepositoryInterface::class);

        $this->assertTrue($repository->hasEntityPermission($user->id, 'invoice', PermissionCatalog::WEB));
        $this->assertFalse($repository->hasEntityPermission(User::factory()->create()->id, 'invoice', PermissionCatalog::WEB));
    }
}
