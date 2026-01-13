<?php

namespace Tests\Feature\Helpers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserHelpersFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // UserHelpers has been removed from the codebase. These tests are intentionally skipped.
        $this->markTestSkipped('UserHelpers has been removed; behavior is now covered by AppServiceProviderFeatureTest and direct Auth/backpack helpers.');
    }

    #[Test]
    public function placeholder(): void
    {
        $this->assertTrue(true);
    }
}
