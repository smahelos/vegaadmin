<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use Illuminate\Support\Facades\Hash;

class ProfileControllerFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CreatesFrontendTestEnvironment;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendTestEnvironment();
        // Ensure user has necessary permission (frontend.can_create_edit_user) for UserRequest authorize
        $perm = Permission::where('name','frontend.can_create_edit_user')->where('guard_name','web')->first();
        if ($perm) {
            $this->user->givePermissionTo($perm);
        }
    }

    #[Test]
    public function edit_requires_authentication(): void
    {
        $response = $this->get(route('frontend.profile.edit', ['locale' => 'cs']));
        $response->assertRedirect('/login');
    }

    #[Test]
    public function edit_displays_profile_form(): void
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(route('frontend.profile.edit', ['locale' => 'cs']));
        $response->assertStatus(200);
        $response->assertViewIs('frontend.profile.edit');
        $response->assertViewHasAll(['user','userFields','passwordFields']);
    }

    #[Test]
    public function update_profile_success(): void
    {
        $this->actingAs($this->user, 'web');
        $payload = [
            'name' => 'Updated Name',
            'email' => 'updated_' . uniqid() . '@example.com',
            'street' => 'Street 1',
            'city' => 'City',
            'zip' => '12345',
            'country' => 'CZ',
        ];
        $response = $this->put(route('frontend.profile.update', ['locale' => 'cs']), $payload);
        
        // Debug: check for errors
        if($response->getSession()->has('errors')) {
            dump('Session errors:', $response->getSession()->get('errors')->all());
        }
        
        $response->assertRedirect(route('frontend.profile.edit', ['locale' => 'cs']));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Updated Name']);
    }

    #[Test]
    public function update_profile_validation_errors(): void
    {
        $this->actingAs($this->user);
        $payload = [
            'name' => '',
            'email' => 'bad-email'
        ];
        $response = $this->put(route('frontend.profile.update', ['locale' => 'cs']), $payload);
        // Profile form obsahuje len name a email fields, address fields nie sú required
        $response->assertSessionHasErrors(['name','email']);
    }

    #[Test]
    public function update_password_success(): void
    {
        $this->actingAs($this->user, 'web');
        $currentPassword = 'Passw0rd!';
        $this->user->password = Hash::make($currentPassword);
        $this->user->save();

        $payload = [
            'current_password' => $currentPassword,
            'password' => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!'
        ];
        $response = $this->put(route('frontend.profile.update.password', ['locale' => 'cs']), $payload);
        $response->assertRedirect(route('frontend.profile.edit', ['locale' => 'cs']));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function update_password_fails_with_wrong_current(): void
    {
        $this->actingAs($this->user, 'web');
        $this->user->password = Hash::make('Correct123');
        $this->user->save();

        $payload = [
            'current_password' => 'WrongPass',
            'password' => 'AnotherPass123',
            'password_confirmation' => 'AnotherPass123'
        ];
        $response = $this->put(route('frontend.profile.update.password', ['locale' => 'cs']), $payload);
        $response->assertSessionHasErrors(['current_password']);
    }
}
