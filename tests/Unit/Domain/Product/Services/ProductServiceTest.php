<?php

namespace Tests\Unit\Domain\Product\Services;

use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\Contracts\ProductServiceInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\Services\ProductService;
use App\Domain\Product\Validation\ProductCreationValidator;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Shared\Str\Contracts\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductService $service;
    private ProductDtoReadRepositoryInterface $readRepo;
    private object $readSpy; // concrete stub instance to introspect custom properties
    private ProductDtoWriteRepositoryInterface $writeRepo;
    private object $writeSpy; // concrete stub instance to introspect captured values
    private ProductCreationValidator $validator;
    private EventPublisherInterface $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        // Default read repo stub
        $this->readSpy = new class implements ProductDtoReadRepositoryInterface {
            public int $count = 0;
            public function getProductsForDropdown(UserId $userId): array { return []; }
            public function getDefaultProduct(UserId $userId): ?ProductDTO { return null; }
            public function findByIdAny(ProductId $id): ?ProductDTO { return null; }
            public function findByIdForUser(ProductId $id, UserId $userId): ?ProductDTO { return null; }
            public function allForAdmin(): array { return []; }
            public function allForUser(UserId $userId): array { return []; }
            public function getUserProductCount(UserId $userId): int { return $this->count; }
        };
        $this->readRepo = $this->readSpy;

        // Default write repo stub
        $this->writeSpy = new class implements ProductDtoWriteRepositoryInterface {
            public ?ProductWriteData $capturedCreate = null;
            public ?array $capturedUpdate = null; // [id=>ProductId, data=>ProductWriteData]
            public ?ProductId $capturedDelete = null;
            public function create(ProductWriteData $writeData): ProductDTO {
                $this->capturedCreate = $writeData;
                $priceMoney = isset($writeData->price)
                    ? \App\Domain\Shared\Money\ValueObjects\Money::fromString(number_format((float)$writeData->price, 2, '.', ''), strtoupper($writeData->currency ?? 'CZK'))
                    : null;
                return new ProductDTO(
                    id: 1,
                    name: $writeData->name,
                    description: $writeData->description,
                    slug: $writeData->slug,
                    image: is_string($writeData->image) ? $writeData->image : null,
                    price: $priceMoney,
                    unit: $writeData->unit,
                    category_id: $writeData->category_id,
                    category: null,
                    supplier_id: $writeData->supplier_id,
                    supplier: null,
                    currency: $writeData->currency,
                    tax_id: $writeData->tax_id,
                    tax: null,
                    invoices: [],
                    created_at: null,
                    updated_at: null,
                    user_id: $writeData->user_id,
                    is_default: (bool)($writeData->is_default ?? false),
                    is_active: true,
                );
            }
            public function update(ProductId $id, ProductWriteData $writeData): ProductDTO {
                $this->capturedUpdate = ['id' => $id, 'data' => $writeData];
                $priceMoney = isset($writeData->price)
                    ? \App\Domain\Shared\Money\ValueObjects\Money::fromString(number_format((float)$writeData->price, 2, '.', ''), strtoupper($writeData->currency ?? 'CZK'))
                    : null;
                return new ProductDTO(
                    id: $id->getValue(),
                    name: $writeData->name,
                    description: $writeData->description,
                    slug: $writeData->slug,
                    image: is_string($writeData->image) ? $writeData->image : null,
                    price: $priceMoney,
                    unit: $writeData->unit,
                    category_id: $writeData->category_id,
                    category: null,
                    supplier_id: $writeData->supplier_id,
                    supplier: null,
                    currency: $writeData->currency,
                    tax_id: $writeData->tax_id,
                    tax: null,
                    invoices: [],
                    created_at: null,
                    updated_at: null,
                    user_id: $writeData->user_id,
                    is_default: (bool)($writeData->is_default ?? false),
                    is_active: true,
                );
            }
            public function delete(ProductId $id): void { $this->capturedDelete = $id; }
            public function deleteByUserId(int $userId): int { return 0; }
        };
        $this->writeRepo = $this->writeSpy;

        $this->validator = new ProductCreationValidator(
            $this->createMock(Str::class)
        );
        // No-op event publisher stub for unit tests
        $this->publisher = new class implements EventPublisherInterface {
            public array $published = [];
            public function publish(DomainEvent $event): void { $this->published[] = $event; }
        };
        $this->service = new ProductService($this->readRepo, $this->writeRepo, $this->validator, $this->publisher);
    }

    // Structural tests
    #[Test] public function service_can_be_instantiated(): void { $this->assertInstanceOf(ProductService::class, $this->service); }
    #[Test] public function service_implements_interface(): void { $this->assertInstanceOf(ProductServiceInterface::class, $this->service); }
    #[Test] public function public_methods_count_excluding_constructor(): void
    {
        $r = new \ReflectionClass($this->service);
        $pub = array_filter($r->getMethods(\ReflectionMethod::IS_PUBLIC), fn($m)=>$m->getDeclaringClass()->getName()===ProductService::class && $m->getName()!=='__construct');
        // create, update, delete, findAny, findForUser, getDefaultProduct, getUserProductCount
        $this->assertCount(7, $pub);
    }
    #[Test] public function all_public_methods_have_return_types(): void
    {
        $r = new \ReflectionClass($this->service);
        foreach ($r->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->getDeclaringClass()->getName() === ProductService::class && $m->getName() !== '__construct') {
                $this->assertNotNull($m->getReturnType(), $m->getName().' missing return type');
            }
        }
    }

    // Behavior tests
    #[Test]
    public function create_product_sets_default_when_first_product(): void
    {
        // Simulate first product
        $this->readSpy->count = 0;
        $data = ProductWriteData::fromArray(['name' => 'Sample Product']);
        $result = $this->service->createProduct($data, UserId::fromInt(1));

        $this->assertNotNull($this->writeSpy->capturedCreate);
        $this->assertTrue($this->writeSpy->capturedCreate->is_default);
        $this->assertSame('Sample Product', $result->name);
        $this->assertSame(1, $result->user_id);
        $this->assertTrue($result->is_default);
    }

    #[Test]
    public function update_product_passes_through_to_repository(): void
    {
        $id = ProductId::fromInt(10);
        $write = ProductWriteData::fromArray(['name' => 'New Name']);
        $dto = $this->service->updateProduct($id, $write);

        $this->assertNotNull($this->writeSpy->capturedUpdate);
        $this->assertSame(10, $this->writeSpy->capturedUpdate['id']->getValue());
        $this->assertSame('New Name', $this->writeSpy->capturedUpdate['data']->name);
        $this->assertSame('New Name', $dto->name);
    }

    #[Test]
    public function delete_product_calls_repository(): void
    {
        $id = ProductId::fromInt(77);
        $this->service->deleteProduct($id);
        $this->assertNotNull($this->writeSpy->capturedDelete);
        $this->assertSame(77, $this->writeSpy->capturedDelete->getValue());
    }

    #[Test]
    public function find_and_count_methods_delegate_to_read_repository(): void
    {
        // Custom stub to return specific values
        $customRead = new class implements ProductDtoReadRepositoryInterface {
            public function getProductsForDropdown(UserId $userId): array { return []; }
            public function getDefaultProduct(UserId $userId): ?ProductDTO { return ProductDTO::fromArray(['id' => 1, 'name' => 'P', 'user_id' => $userId->toInt(), 'is_default' => false, 'is_active' => true]); }
            public function findByIdAny(ProductId $id): ?ProductDTO { return ProductDTO::fromArray(['id' => $id->getValue(), 'name' => 'Any', 'user_id' => 0, 'is_default' => false, 'is_active' => true]); }
            public function findByIdForUser(ProductId $id, UserId $userId): ?ProductDTO { return ProductDTO::fromArray(['id' => $id->getValue(), 'name' => 'ForUser', 'user_id' => $userId->toInt(), 'is_default' => false, 'is_active' => true]); }
            public function allForAdmin(): array { return []; }
            public function allForUser(UserId $userId): array { return []; }
            public function getUserProductCount(UserId $userId): int { return 5; }
        };
    $service = new ProductService($customRead, $this->writeRepo, $this->validator, $this->publisher);

        $this->assertSame(5, $service->getUserProductCount(UserId::fromInt(1)));
        $this->assertSame('P', $service->getDefaultProduct(UserId::fromInt(123))?->name);
        $this->assertSame('Any', $service->findProductAny(ProductId::fromInt(9))?->name);
        $this->assertSame('ForUser', $service->findProductForUser(ProductId::fromInt(8), UserId::fromInt(22))?->name);
    }
}
