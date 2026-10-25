<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\PaymentDtoWriteRepositoryInterface;
use App\Domain\Payment\DTO\PaymentWriteData;
use App\Models\Payment;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EloquentPaymentDtoWriteRepositoryTest extends TestCase
{
    #[Test]
    public function updates_payment_and_returns_its_dto(): void
    {
        $payment = Payment::factory()->create([
            'status' => 'pending',
            'gateway_data' => null,
        ]);

        $dto = $this->app->make(PaymentDtoWriteRepositoryInterface::class)->updateById(
            $payment->id,
            new PaymentWriteData(status: 'processing'),
        );

        $this->assertSame($payment->id, $dto->id);
        $this->assertSame('processing', $dto->status);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'processing',
        ]);
    }
}
