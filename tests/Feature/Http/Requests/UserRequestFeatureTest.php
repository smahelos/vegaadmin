<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRequestFeatureTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = UserRequest::create(
            '/',
            'PUT',
            $data,
        );
        $request->setUserResolver(fn () => $this->user);

        return Validator::make(
            $data,
            $request->rules(),
            [],
            $request->attributes(),
        );
    }

    #[Test]
    public function guest_is_not_authorized(): void
    {
        $this->assertFalse((new UserRequest)->authorize());
    }

    #[Test]
    public function signed_in_user_is_authorized(): void
    {
        $request = new UserRequest;
        $request->setUserResolver(fn () => $this->user);

        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function name_and_email_pass(): void
    {
        $this->assertFalse($this->validate(['name' => 'Jan Novák', 'email' => 'jan_' . uniqid() . '@example.com'])->fails());
    }

    #[Test]
    public function own_email_passes(): void
    {
        $this->assertFalse($this->validate(['name' => 'Jan Novák', 'email' => $this->user->email])->fails());
    }

    #[Test]
    public function another_users_email_fails(): void
    {
        $other = User::factory()->create();

        $this->assertTrue($this->validate(['name' => 'Jan Novák', 'email' => $other->email])->errors()->has('email'));
    }

    #[Test]
    public function missing_name_and_invalid_email_fail_with_field_labels(): void
    {
        $errors = $this->validate(['name' => '', 'email' => 'invalid'])->errors();

        $this->assertStringContainsString(__('users.fields.name'), $errors->first('name'));
        $this->assertStringContainsString(__('users.fields.email'), $errors->first('email'));
    }
}
