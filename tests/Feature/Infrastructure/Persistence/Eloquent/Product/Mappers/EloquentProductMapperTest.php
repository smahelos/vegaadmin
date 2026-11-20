<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Product\Mappers;

use App\Infrastructure\Persistence\Eloquent\Product\Mappers\EloquentProductMapper;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ValueError;

final class EloquentProductMapperTest extends TestCase
{
    #[Test]
    public function refuses_a_stored_currency_outside_the_enum(): void
    {
        $product = new Product(
            [
                'price' => '10.00',
                'currency' => 'XYZ',
            ],
        );

        $this->expectException(ValueError::class);

        (new EloquentProductMapper)->toDto($product);
    }
}
