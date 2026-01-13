<?php

namespace Tests\Feature\Exceptions;

use App\Domain\User\Exceptions\EntityLimitExceededException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EntityLimitExceededExceptionTest extends TestCase
{
    #[Test]
    public function json_requests_receive_translated_payload(): void
    {
        // Arrange: test route that throws the exception
        Route::middleware('web')->get('/test/json-limit', function () {
            throw new EntityLimitExceededException(
                limitData: ['entity' => 'invoice', 'limit' => 3]
            );
        });

        // Act: call as JSON
        $resp = $this->getJson('/test/json-limit');

        // Assert
        $resp->assertStatus(403);
        $resp->assertJsonStructure(['message', 'message_key', 'context', 'limit']);
        $resp->assertJsonFragment([
            'message_key' => 'invoices.messages.limit_exceeded',
        ]);
        $expected = __(
            'invoices.messages.limit_exceeded',
            ['entity' => 'invoice', 'limit' => 3]
        );
        $this->assertEquals($expected, $resp->json('message'));
        $this->assertEquals(['entity' => 'invoice', 'limit' => 3], $resp->json('context'));
        $this->assertEquals(['entity' => 'invoice', 'limit' => 3], $resp->json('limit'));
    }

    #[Test]
    public function invoice_routes_redirect_back_with_flash_message(): void
    {
        // Arrange: named route under frontend.invoice*
        Route::middleware('web')->post('/test/invoice-limit', function () {
            throw new EntityLimitExceededException(
                limitData: ['entity' => 'invoice', 'limit' => 5]
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
    public function supplier_routes_redirect_back_with_flash_message(): void
    {
        // Arrange: named route under frontend.supplier*
        Route::middleware('web')->post('/test/supplier-limit', function () {
            throw new EntityLimitExceededException(
                limitData: ['entity' => 'supplier', 'limit' => 2]
            );
        })->name('frontend.supplier.testLimit');

        // Act
        $resp = $this->from('/supplier-prev')
            ->post('/test/supplier-limit', []);

        // Assert
        $resp->assertRedirect('/supplier-prev');
        $resp->assertSessionHas('error', __('suppliers.messages.limit_exceeded'));
    }
}
