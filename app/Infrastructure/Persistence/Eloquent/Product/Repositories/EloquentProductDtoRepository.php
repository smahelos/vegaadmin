<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductSearch;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\Enums\ProductSort;
use App\Domain\Product\Exceptions\ProductInUseException;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Product\Mappers\EloquentProductMapper;
use App\Models\InvoiceProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;

final class EloquentProductDtoRepository implements ProductDtoReadRepositoryInterface, ProductDtoWriteRepositoryInterface
{
    /** MySQL ER_ROW_IS_REFERENCED_2: a foreign key with ON DELETE RESTRICT refused the delete */
    private const ROW_IS_REFERENCED = 1451;

    private const RELATIONS = [
        'category',
        'tax',
        'invoices',
    ];

    public function __construct(
        private readonly EloquentProductMapper $mapper,
    ) {}

    public function findById(ProductId $id, ReadScope $scope): ?ProductDTO
    {
        $product = $this->ownedQuery($id, $scope->owner);
        $product->with(self::RELATIONS);
        $found = $product->first();

        return $found === null ? null : $this->mapper->toDto($found);
    }

    public function search(
        ReadScope $scope,
        ProductSearch $search,
        PageRequest $page,
    ): PaginatedResult {
        $query = Product::query();
        $query->where('user_id', $scope->owner->toInt());
        $query->with(self::RELATIONS);
        if ($search->term !== null && $search->term !== '') {
            $term = '%' . $search->term . '%';
            $query->where(
                function (Builder $matches) use ($term): void {
                    $matches->where(
                        'name',
                        'like',
                        $term,
                    );
                    $matches->orWhere(
                        'description',
                        'like',
                        $term,
                    );
                },
            );
        }
        if ($search->active !== null) {
            $query->where('is_active', $search->active);
        }
        if ($search->excluded !== []) {
            $query->whereNotIn(
                'id',
                array_map(
                    fn (ProductId $id): int => $id->getValue(),
                    $search->excluded,
                ),
            );
        }
        $column = $search->sort->value;
        if ($search->sort === ProductSort::INVOICES) {
            $query->withCount('invoices');
            $column = 'invoices_count';
        }
        $query->orderBy($column, $search->ascending ? 'asc' : 'desc');
        $found = $query->paginate(
            $page->perPage(),
            ['*'],
            'page',
            $page->page(),
        );

        return new PaginatedResult(
            array_map(
                fn (Product $product): ProductDTO => $this->mapper->toDto($product),
                $found->items(),
            ),
            $found->total(),
            $found->perPage(),
            $found->currentPage(),
        );
    }

    public function getUserProductCount(UserId $userId): int
    {
        $query = Product::where('user_id', $userId->toInt());

        return $query->count();
    }

    public function create(WriteScope $scope, ProductWriteData $writeData): ProductDTO
    {
        $attributes = $writeData->toArray();
        $attributes['user_id'] = $scope->owner->toInt();
        $product = Product::create($attributes);

        return $this->mapper->toDto($product);
    }

    public function update(
        ProductId $id,
        WriteScope $scope,
        ProductWriteData $writeData,
    ): ProductDTO {
        $query = $this->ownedQuery($id, $scope->owner);
        $product = $query->firstOrFail();
        $attributes = $writeData->toArray();
        unset($attributes['user_id']);
        $product->update($attributes);
        $product->load(self::RELATIONS);

        return $this->mapper->toDto($product);
    }

    public function isUsedOnInvoices(ProductId $id): bool
    {
        $query = InvoiceProduct::where('product_id', $id->getValue());

        return $query->exists();
    }

    public function delete(ProductId $id, WriteScope $scope): void
    {
        $query = $this->ownedQuery($id, $scope->owner);
        try {
            $query->delete();
        } catch (QueryException $e) {
            // the service checks for invoice lines first; this catches one added in the meantime
            if (($e->errorInfo[1] ?? null) !== self::ROW_IS_REFERENCED) {
                throw $e;
            }

            throw ProductInUseException::onInvoices($id, $e);
        }
    }

    /**
     * @return Builder<Product>
     */
    private function ownedQuery(ProductId $id, UserId $owner): Builder
    {
        $query = Product::query();
        $query->whereKey($id->getValue());
        $query->where('user_id', $owner->toInt());

        return $query;
    }
}
