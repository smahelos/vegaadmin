<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable middleware that might cause issues during tests
        $this->withoutMiddleware(\App\Http\Middleware\SetLocale::class);
    }
    
    /**
     * Sign in as a Backpack user
     *
     * @param User|null $user
     * @return $this
     */
    protected function actingAsBackpackUser(?User $user = null)
    {
        $user = $user ?: User::factory()->create();
        $guard = config('backpack.base.guard') ?: 'backpack';
        
        $this->actingAs($user, $guard);
        
        return $this;
    }
}
