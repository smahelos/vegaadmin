<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Http\Requests\ProductRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Traits\ProductFormFields;

class ProductController extends Controller
{
    use AuthorizesRequests;
    use ProductFormFields;

    /**
     * Display a listing of the user's products.
     */
    public function index()
    {
        // $products = Product::where('user_id', Auth::id())
        //     ->with(['category', 'tax'])
        //     ->paginate(10);
        
        return view('frontend.products.index');
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        // Get product categories for dropdown
        $productCategories = ProductCategory::pluck('slug', 'id')->toArray();

        // Get tax rates for dropdown
        $taxRates = Tax::where('slug', 'dph')
            ->pluck('rate', 'id')
            ->toArray();

        foreach($taxRates as $key => $value) {
            $taxRates[$key] = $value . '%';
        }

        $fields = $this->getProductFields($productCategories, $taxRates);
        $categories = ProductCategory::all();
        
        return view('frontend.products.create', compact('productCategories', 'taxRates', 'fields'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        if (Product::where('user_id', Auth::id())->count() === 0) {
            $validatedData['is_default'] = true;
        }

        if ($request->hasFile('image')) {
            $validatedData['image'] = $request->file('image')->store('products', 'public');
        }

        // Generate slug if not provided
        if (empty($validatedData['slug'])) {
            $validatedData['slug'] = \Str::slug($validatedData['name']);
        }

        Product::create($validatedData);

        return redirect()->route('frontend.products')
            ->with('success', trans('products.messages.product_created'));
    }

    public function show(int $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('view', $product);

        return view('frontend.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(int $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        // Get product categories for dropdown
        $productCategories = ProductCategory::pluck('slug', 'id')->toArray();

        // Get tax rates for dropdown
        $taxRates = Tax::where('slug', 'dph')
            ->pluck('rate', 'id')
            ->toArray();

        foreach($taxRates as $key => $value) {
            $taxRates[$key] = $value . '%';
        }

        $fields = $this->getProductFields($productCategories, $taxRates);

        $categories = ProductCategory::all();
        
        return view('frontend.products.edit', compact('product', 'productCategories', 'taxRates', 'fields'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(ProductRequest $request, int $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);
        
        $data = $request->validated();
        $data['is_default'] = isset($data['is_default']) && $data['is_default'] == 1;
        $data['is_active'] = isset($data['is_active']) && $data['is_active'] == 1;
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = \Str::slug($data['name']);
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);
        
        return redirect()->route('frontend.products')
            ->with('success', trans('products.messages.product_updated'));
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        
        $product->delete();
        
        return redirect()->route('frontend.products')
            ->with('success', trans('products.messages.product_deleted'));
    }
}
