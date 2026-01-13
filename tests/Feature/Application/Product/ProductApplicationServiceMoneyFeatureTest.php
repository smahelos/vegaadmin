<?php

namespace Tests\Feature\Application\Product;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class ProductApplicationServiceMoneyFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    protected ProductApplicationServiceInterface $productApp;

    protected function setUp(): void
    {
        parent::setUp();
    $this->productApp = app(ProductApplicationServiceInterface::class);
    }

    #[Test]
    public function create_product_with_price_money(): void
    {
        $user = User::factory()->create();
        $data = [
            'name' => 'Money DTO Product',
            'price_money' => ['amount' => '123.45', 'currency' => 'czk'],
            'unit' => 'ks',
        ];
    $productDto = $this->productApp->createProduct($data, $user->id);

        $this->assertSame('123.45', $productDto->price?->getAmount());
        $this->assertSame('CZK', $productDto->price?->getCurrency());
    }

    #[Test]
    public function update_product_with_legacy_price_and_currency(): void
    {
        $user = User::factory()->create();
    $created = $this->productApp->createProduct([
            'name' => 'Legacy Price Product',
            'price' => 10,
            'currency' => 'EUR',
            'unit' => 'ks',
        ], $user->id);

    $updated = $this->productApp->updateProduct($created->id, [
            'price' => '20.5',
            'currency' => 'eur',
        ], $user->id);

        $this->assertSame('20.50', $updated->price?->getAmount());
        $this->assertSame('EUR', $updated->price?->getCurrency());
    }
}
