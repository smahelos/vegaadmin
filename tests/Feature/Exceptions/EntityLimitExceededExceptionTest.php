<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use App\Domain\User\Exceptions\EntityLimitExceededException;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EntityLimitExceededExceptionTest extends TestCase
{
    #[Test]
    public function json_requests_receive_translated_payload(): void
    {
        // Arrange: test route that throws the exception
        Route::middleware('web')->get('/test/json-limit', function (): void {
            throw new EntityLimitExceededException(
                limitData: ['entity' => 'invoice', 'limit' => 3],
            );
        });

        // Act: call as JSON
        $resp = $this->getJson('/test/json-limit');

        // Assert
        $resp->assertStatus(403);
        $resp->assertJsonStructure([
            'message',
            'message_key',
            'context',
            'limit',
        ]);
        $resp->assertJsonFragment([
            'message_key' => 'invoices.messages.limit_exceeded',
        ]);
        $expected = __(
            'invoices.messages.limit_exceeded',
            ['entity' => 'invoice', 'limit' => 3],
        );
        $this->assertEquals($expected, $resp->json('message'));
        $this->assertEquals(['entity' => 'invoice', 'limit' => 3], $resp->json('context'));
        $this->assertEquals(['entity' => 'invoice', 'limit' => 3], $resp->json('limit'));
    }

    #[Test]
    public function invoice_routes_redirect_back_with_flash_message(): void
    {
        // Arrange: named route under frontend.invoice*
        Route::middleware('web')->post('/test/invoice-limit', function (): void {
            throw new EntityLimitExceededException(
                limitData: ['entity' => 'invoice', 'limit' => 5],
            );
        })->name('frontend.invoice.testLimit');

        // Act
        $resp = $this->from('/previous')
            ->post('/test/invoice-limit', []);

        // Assert
        $resp->assertRedirect('/previous');
        $resp->assertSessionHas('error', __('invoices.messages.limit_exceeded'));
    }

    #[Test]
    public function business_routes_redirect_back_with_flash_message(): void
    {
        // Arrange: named route under frontend.business*
        Route::middleware('web')->post('/test/business-limit', function (): void {
            throw new EntityLimitExceededException(
                limitData: ['entity' => 'business', 'limit' => 2],
            );
        })->name('frontend.business.testLimit');

        // Act
        $resp = $this->from('/business-prev')
            ->post('/test/business-limit', []);

        // Assert
        $resp->assertRedirect('/business-prev');
        $resp->assertSessionHas('error', __('businesses.messages.limit_exceeded'));
    }
}
