<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Application\Product\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findUserProducts(User $user): Collection
    {
        return Product::where('user_id', $user->id)
            ->with(['category', 'tax', 'supplier', 'invoices'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getUserProductCount(User $user): int
    {
        return Product::where('user_id', $user->id)->count();
    }

    public function findByIdForUser(int $id, User $user): Product
    {
        return Product::where('id',$id)
            ->where('user_id',$user->id)
            ->with(['category', 'tax', 'supplier', 'invoices'])
            ->firstOrFail();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return (bool)$product->delete();
    }

    public function make(array $attributes = []): Product
    {
        return new Product($attributes);
    }

    public function listForUser(int $userId): Collection
    {
        return Product::where('user_id',$userId)->orderByDesc('created_at')->get();
    }

    // Additional methods for DTO repository support
    
    public function getProductsForDropdown(int $userId): array
    {
        return Product::where('user_id', $userId)->pluck('name','id')->toArray();
    }

    public function getDefaultProduct(int $userId): ?Product
    {
        return Product::where('user_id',$userId)->where('is_default',true)->first();
    }

    public function findByIdAny(int $id): ?Product
    {
        return Product::with(['category', 'tax', 'supplier', 'invoices'])->find($id);
    }

    /**
     * @param int $id
     * @param int $userId
     * @return Product|null
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findByIdForUserInt(int $id, int $userId): ?Product
    {
        $product = Product::where('id',$id)
            ->where('user_id',$userId)
            ->with(['category', 'tax', 'supplier', 'invoices'])
            ->first();

        if (!$product) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Product not found for user.");
        }

        return $product;
    }

    public function allForAdmin(): Collection
    {
        return Product::with(['category', 'tax', 'supplier', 'invoices'])->get();
    }

    public function allForUser(int $userId): Collection
    {
        return Product::where('user_id',$userId)
            ->with(['category', 'tax', 'supplier', 'invoices'])
            ->get();
    }

    public function allForInvoice(int $invoiceId): Collection
    {
        return Product::where('invoice_id',$invoiceId)
            ->with(['category', 'tax', 'supplier', 'invoices'])
            ->get();
    }

    public function getUserProductCountInt(int $userId): int
    {
        return Product::where('user_id', $userId)->count();
    }

    public function updateById(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product->fresh();
    }

    public function deleteById(int $id): void
    {
        Product::where('id', $id)->delete();
    }

    public function deleteByUserId(int $userId): int
    {
        return Product::where('user_id', $userId)->delete();
    }
}
