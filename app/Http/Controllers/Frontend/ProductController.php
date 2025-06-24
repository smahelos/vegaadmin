<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\ProductRequest;
use App\Contracts\ProductServiceInterface;
use App\Contracts\ProductRepositoryInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ProductFormFields;

class ProductController extends Controller
{
    use AuthorizesRequests;
    use ProductFormFields;

    /**
     * Product service instance
     *
     * @var ProductServiceInterface
     */
    protected $productService;

    /**
     * Product repository instance
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Constructor
     *
     * @param ProductServiceInterface $productService
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductServiceInterface $productService,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productService = $productService;
        $this->productRepository = $productRepository;
    }

    /**
     * Display a listing of the user's products.
     */
    public function index()
    {
        // $products = Product::where('user_id', Auth::id())
        //     ->with(['category', 'tax'])
        //     ->paginate(10);

        // we load grid by livewire ProductList component
        return view('frontend.products.index');
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        // Get form data from service
        $formData = $this->productService->getFormData();
        
        $fields = $this->getProductFields($formData['product_categories'], $formData['tax_rates']);
        
        return view('frontend.products.create', [
            'productCategories' => $formData['product_categories'],
            'taxRates' => $formData['tax_rates'],
            'fields' => $fields
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductRequest $request)
    {
        $validatedData = $request->validated();
        $user = Auth::user();
        
        // Create product using service
        $this->productService->createProduct($validatedData, $user);

        return redirect()->route('frontend.products', ['locale' => app()->getLocale()])
            ->with('success', trans('products.messages.created'));
    }

    /**
     * Display the specified product.
     * 
     * @param string $locale
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show(string $locale, int $id)
    {
        $user = Auth::user();
        $product = $this->productRepository->findByIdForUser($id, $user);
        $this->authorize('view', $product);

        return view('frontend.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     * 
     * @param string $locale
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit(string $locale, int $id)
    {
        $user = Auth::user();
        $product = $this->productRepository->findByIdForUser($id, $user);
        $this->authorize('update', $product);

        // Get form data from service
        $formData = $this->productService->getFormData();
        $fields = $this->getProductFields($formData['product_categories'], $formData['tax_rates']);
        
        return view('frontend.products.edit', [
            'product' => $product,
            'productCategories' => $formData['product_categories'],
            'taxRates' => $formData['tax_rates'],
            'fields' => $fields
        ]);
    }

    /**
     * Update the specified product in storage.
     * 
     * @param \App\Http\Requests\ProductRequest $request
     * @param string $locale
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProductRequest $request, string $locale, int $id)
    {
        $user = Auth::user();
        $product = $this->productRepository->findByIdForUser($id, $user);
        $this->authorize('update', $product);
        
        $data = $request->validated();
        
        // Update product using service
        $this->productService->updateProduct($product, $data);
        
        return redirect()->route('frontend.products', ['locale' => app()->getLocale()])
            ->with('success', trans('products.messages.updated'));
    }

    /**
     * Remove the specified product from storage.
     * 
     * @param string $locale
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $locale, Product $product)
    {
        $this->authorize('delete', $product);
        
        // Delete product using service
        $this->productService->deleteProduct($product);
        
        return redirect()->route('frontend.products', ['locale' => app()->getLocale()])
            ->with('success', trans('products.messages.product_deleted'));
    }
}
