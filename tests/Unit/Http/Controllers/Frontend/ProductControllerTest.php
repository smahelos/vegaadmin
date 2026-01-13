<?php

namespace Tests\Unit\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\ProductController;
use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Application\User\Contracts\UELSApplicationServiceInterface;
use App\Models\User;
use App\Domain\Product\DTO\ProductDTO;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

/**
 * Unit tests for ProductController focusing on internal helper logic (getProductsLimit).
 * Per project testing guidelines controllers are mainly covered by Feature tests;
 * here we only exercise pure aggregation logic without hitting database/framework layers.
 */
class ProductControllerTest extends TestCase
{
    private ProductApplicationServiceInterface $productServiceStub;
    private UELSApplicationServiceInterface $limitServiceStub;

    protected function setUp(): void
    {
        parent::setUp();

        // Simple stub classes implementing required interfaces
        $this->productServiceStub = new class implements ProductApplicationServiceInterface {
            public function createProduct(array $data, int $userId): ProductDTO { throw new \RuntimeException('Not used in unit test'); }
            public function updateProduct(int $productId, array $data, int $userId): ProductDTO   { throw new \RuntimeException('Not used in unit test'); }
            public function deleteProduct(int $productId, int $userId): void { return; }
            public function getFormData(): array { return []; }
            public function findProduct(int $userId, int $id): ProductDTO { throw new \RuntimeException('Not used in unit test'); }
            public function listProducts(int $userId): Collection { throw new \RuntimeException('Not used in unit test'); }
            public function getProductsDropdown(int $userId): array { throw new \RuntimeException('Not used in unit test'); }
            public function getDefaultProduct(int $userId): ?ProductDTO { throw new \RuntimeException('Not used in unit test'); }
            public function getUserProductCount(int $userId): int { return 0; }
            public function authorizeViewProduct(int $userId, int $productId): void { return; }
            public function authorizeUpdateProduct(int $userId, int $productId): void { return; }
            public function authorizeDeleteProduct(int $userId, int $productId): void { return; }
            public function handleProductImage(\Illuminate\Http\UploadedFile|string|null $image, ?string $oldImage = null): ?string { return null; }
            public function generateSlug(string $name): string { return 'sample-slug'; }
            public function invalidateFormDataCache(): bool { return true; }
            public function getAllCategories(): array { return []; }
            public function getAllSuppliers(): array { return []; }
            public function getAllSuppliersForUser(int $userId): array { return []; }
            public function clearCategoriesCache(): void { return; }
        };

        // Configurable limit service stub
        $this->limitServiceStub = 
            new class implements UELSApplicationServiceInterface {
                public string $bestPeriod = 'daily';
                public array $checkResponse = [];
                public array $usageStats = [];

                public function getEntityLimitInfo(?int $userId, string $entityType): array { return []; }
                public function canUserCreateEntity(?int $userId, string $entityType): bool { return true; }
                public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array {
                    $stats = array_merge($this->checkResponse, $this->usageStats);
                    if (!array_key_exists('current_usage', $stats)) {
                        $stats['current_usage'] = 0;
                    }
                    if (!array_key_exists('can_create', $stats) && array_key_exists('allowed', $stats)) {
                        $stats['can_create'] = (bool) $stats['allowed'];
                    }
                    return $stats;
                }
                public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string { return $this->bestPeriod; }
                public function getUserPermissionLimits(?int $userId, string $entityType): Collection { return collect(); }
            };
    }

    #[Test]
    public function get_products_limit_returns_expected_structure_for_authenticated_user(): void
    {
        $user = new User();
        $user->id = 123; // Unsaved, sufficient for logic under test

        // Mock Auth facade
        Auth::shouldReceive('user')->andReturn($user);

        // Configure stub responses
        $this->limitServiceStub->bestPeriod = 'monthly';
        $this->limitServiceStub->checkResponse = [
            'allowed' => true,
            'limit' => 10,
            'current_usage' => 3,
        ];
        $this->limitServiceStub->usageStats = [
            'current_usage' => 3,
        ];

        $controller = new ProductController($this->productServiceStub, $this->limitServiceStub);

        $result = $controller->getProductsLimitStats($user);

        $this->assertIsArray($result);
        $this->assertSame(10, $result['limit']);
        $this->assertSame(3, $result['current_usage']);
        $this->assertTrue($result['allowed']);
    }

    #[Test]
    public function get_products_limit_returns_default_values_when_no_user(): void
    {
        Auth::shouldReceive('user')->andReturn(null);

        // Limit service should NOT be called when user is null
        // No stubs for limit service when user is null (methods should not be called)

        $controller = new ProductController($this->productServiceStub, $this->limitServiceStub);

        $result = $controller->getProductsLimitStats();

        $this->assertIsArray($result);
        $this->assertSame(0, $result['limit']);
        $this->assertSame(0, $result['current_usage']);
        $this->assertFalse($result['allowed']);
    }
}
