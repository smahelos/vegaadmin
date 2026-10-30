<?php

namespace Tests\Feature\Integration;

use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Http\Requests\InvoiceRequest;
use App\Models\EntityLimit;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

class UniversalEntityLimitSystemIntegrationTest extends TestCase
{
    use CreatesFrontendTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up frontend test environment with roles and permissions
        $this->setUpFrontendTestEnvironment();

        // Give frontend permissions to user (instead of backpack permissions)
        $permissions = [
            'frontend.can_create_edit_invoice',
            'frontend.can_create_contact',
            'frontend.can_create_business',
        ];
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();
            if ($permission) {
                $this->user->givePermissionTo($permission);
            }
        }
    }

    #[Test]
    public function updates_bypass_limit_checks(): void
    {
        $this->actingAs($this->user);

        // Set up a strict limit (0 new invoices allowed)
        EntityLimit::factory()
            ->forEntity('invoice')
            ->countLimit(0)
            ->monthly()
            ->active()
            ->create();

        // New creation should fail
        $newRequest = new InvoiceRequest;
        $newRequest->setMethod('POST');
        $newRequest->setRouteResolver(function () {
            return (object) ['parameter' => function ($name) {
                return null;
            }]; // New creation
        });

        $this->expectException(EntityLimitExceededException::class);
        $newRequest->authorize();
    }

    #[Test]
    public function updates_bypass_limit_checks_update_allowed(): void
    {
        $this->actingAs($this->user);

        // Set up a strict limit (0 new invoices allowed)
        EntityLimit::factory()
            ->forEntity('invoice')
            ->countLimit(0)
            ->monthly()
            ->active()
            ->create();

        // But update should succeed even with 0 limit
        $updateRequest = new InvoiceRequest;
        $updateRequest->setMethod('PUT');
        $updateRequest->setRouteResolver(function () {
            return (object) ['parameter' => function ($name) {
                return 'existing-invoice-id';
            }]; // Update
        });

        $this->assertTrue($updateRequest->authorize());
    }

    #[Test]
    public function unlimited_access_bypasses_all_limits(): void
    {
        // Create a user with unlimited access
        $unlimitedPermission = Permission::firstOrCreate(['name' => 'can_create_invoice_unlimited', 'guard_name' => 'backpack']);
        $this->user->givePermissionTo($unlimitedPermission);
        $this->actingAs($this->user);

        // Set up a strict limit (0 new invoices allowed)
        EntityLimit::factory()
            ->forEntity('invoice')
            ->countLimit(0)
            ->monthly()
            ->active()
            ->create();

        // Should succeed despite limit due to unlimited access
        $request = new InvoiceRequest;
        $request->setRouteResolver(function () {
            return (object) ['parameter' => function ($name) {
                return null;
            }]; // New creation
        });

        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function system_works_without_any_limits_configured(): void
    {
        $this->actingAs($this->user);

        // No limits configured - should always allow
        $request = new InvoiceRequest;
        $request->setRouteResolver(function () {
            return (object) ['parameter' => function ($name) {
                return null;
            }]; // New creation
        });

        $this->assertTrue($request->authorize());
    }
}
