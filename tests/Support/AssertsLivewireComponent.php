<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livewire's assertSeeLivewire macro exists only while tests run, so Larastan cannot see it; this makes the same check.
 */
trait AssertsLivewireComponent
{
    /**
     * @param  TestResponse<Response>  $response
     */
    private function assertSeesLivewire(TestResponse $response, string $component): void
    {
        $name = json_encode(['name' => $component], JSON_THROW_ON_ERROR);
        $response->assertSee(
            trim(
                htmlspecialchars($name),
                '{}',
            ),
            false,
        );
    }
}
