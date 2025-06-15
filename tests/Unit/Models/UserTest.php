<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

/**
 * Unit tests for User Model
 * 
 * Tests internal model structure, fillable attributes, hidden attributes, and casts
 * These tests do not require database interactions and focus on model configuration
 */
class UserTest extends TestCase
{
    /**
     * Test that user fillable attributes are correctly defined.
     *
     * @return void
     */
    public function test_user_has_correct_fillable_attributes()
    {
        $user = new User();
        $fillable = [
            'name',
            'email',
            'email_verified_at',
            'password',
            'remember_token',
        ];

        $this->assertEquals($fillable, $user->getFillable());
    }

    /**
     * Test that hidden attributes are correctly defined.
     *
     * @return void
     */
    public function test_user_has_correct_hidden_attributes()
    {
        $user = new User();
        $hidden = [
            'password',
            'remember_token',
        ];

        $this->assertEquals($hidden, $user->getHidden());
    }

    /**
     * Test that the user model has correctly defined cast attributes.
     *
     * @return void
     */
    public function test_user_has_correct_casts()
    {
        $user = new User();
        $this->assertArrayHasKey('email_verified_at', $user->getCasts());
        $this->assertArrayHasKey('password', $user->getCasts());
        $this->assertEquals('datetime', $user->getCasts()['email_verified_at']);
        $this->assertEquals('hashed', $user->getCasts()['password']);
    }
}
