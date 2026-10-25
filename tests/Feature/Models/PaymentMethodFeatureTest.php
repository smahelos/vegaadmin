<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\PaymentMethod;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PaymentMethodFeatureTest extends TestCase
{
    #[Test]
    public function every_payment_method_code_is_seeded(): void
    {
        $seeded = PaymentMethod::query()->orderBy('slug')->pluck('slug')->all();
        $codes = array_map(fn (PaymentMethodCode $code): string => $code->value, PaymentMethodCode::cases());
        sort($codes);

        $this->assertSame($codes, $seeded);
    }

    #[Test]
    public function translated_name_is_the_translation_of_the_method(): void
    {
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::CASH->value)->firstOrFail();

        $this->assertSame(__('payment_methods.methods.cash'), $paymentMethod->translated_name);
    }
}
