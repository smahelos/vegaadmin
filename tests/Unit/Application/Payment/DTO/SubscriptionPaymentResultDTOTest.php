<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Payment\DTO;

use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SubscriptionPaymentResultDTOTest extends TestCase
{
    #[Test]
    public function maps_a_started_payment_with_its_gateway_redirect(): void
    {
        $result = SubscriptionPaymentResultDTO::fromArray([
            'success' => true,
            'payment_id' => 555,
            'status' => 'pending',
            'payment_url' => 'https://gateway/redirect',
        ]);

        $this->assertTrue($result->success);
        $this->assertSame(555, $result->paymentId);
        $this->assertSame('pending', $result->status);
        $this->assertSame('https://gateway/redirect', $result->redirectUrl);
        $this->assertNull($result->error);
    }

    #[Test]
    public function keeps_the_gateway_error_of_a_failed_payment(): void
    {
        $result = SubscriptionPaymentResultDTO::fromArray([
            'success' => false,
            'error' => 'gateway_down',
        ]);

        $this->assertFalse($result->success);
        $this->assertSame('gateway_down', $result->error);
    }

    #[Test]
    public function result_without_a_success_flag_is_a_failure(): void
    {
        $this->assertFalse(SubscriptionPaymentResultDTO::fromArray([])->success);
    }
}
