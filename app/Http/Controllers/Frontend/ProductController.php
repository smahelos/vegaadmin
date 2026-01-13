<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Application\User\Contracts\UELSApplicationServiceInterface as UELSService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Infrastructure\Forms\Products\ProductFormFields;

class ProductController extends Controller
{
    use AuthorizesRequests;
    use ProductFormFields; // Frontend limits handled via observers now

    /**
     * Product service instance
     *
    * @var ProductApplicationServiceInterface
     */
    protected $productService;

    /**
    * Universal Entity Limit application service
    *
    * @var UELSService
    */
    protected $limitService;

    /**
     * @param ProductApplicationServiceInterface $productService
     * @param UELSService $limitService
     */
    public function __construct(
        ProductApplicationServiceInterface $productService,
        UELSService $limitService
    ) {
        $this->productService = $productService;
        $this->limitService = $limitService;
    }

    /**
     * Display a listing of the user's products.
     */
    public function index()
    {
        // get current logged user's product limits
        $limitsData = $this->getProductsLimitStats();

        // we load grid by livewire ProductList component
        return view('frontend.products.index', compact('limitsData'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        // Get form data from service
        $formData = $this->productService->getFormData();

        $fields = $this->getProductFields($formData['product_categories'], $formData['tax_rates']);

        // get current logged user's product limits
        $limitsData = $this->getProductsLimitStats();

        return view('frontend.products.create', [
            'productCategories' => $formData['product_categories'],
            'taxRates' => $formData['tax_rates'],
            'fields' => $fields,
            'limitsData' => $limitsData
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductRequest $request)
    {
        $validatedData = $request->validated();
        $user = Auth::user();

        // Check entity limits before creation
        if ($user) {
            $canCreate = $this->limitService->canUserCreateEntity($user->id, 'product');
            if (!$canCreate) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', trans('products.messages.limit_exceeded'));
            }
        }

        // Handle image upload if present
        if ($request->hasFile('image')) {
            $validatedData['image'] = $request->file('image');
        }

        try {
            $product = $this->productService->createProduct($validatedData, $user->id);
            $locale = $request->route('locale') ?? app()->getLocale();
            return redirect()->route('frontend.products', ['locale' => $locale])
                ->with('success', trans('products.messages.created'));
        } catch (ValidationException $e) {
            // Handle file upload validation errors with detailed messages
             return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', trans('products.messages.validation_failed'));
        } catch (\Throwable $e) {
            // Log the actual error for debugging
            Log::error('Product creation failed', [
                'user_id' => $user->id ?? 'guest',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', trans('products.messages.error_create') . ': ' . $e->getMessage());
        }
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

        // Efficient authorization without loading Eloquent model
        $this->productService->authorizeViewProduct($user->id, $id);

        $product = $this->productService->findProduct($user->id, $id); // Get DTO for view

        // get current logged user's product limits
        $limitsData = $this->getProductsLimitStats($user);

        return view('frontend.products.show', compact('product', 'limitsData'));
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

        // Efficient authorization without loading Eloquent model
        $this->productService->authorizeUpdateProduct($user->id, $id);

        $product = $this->productService->findProduct($user->id, $id); // Get DTO for view

        // Get form data from service
        $formData = $this->productService->getFormData();
        $fields = $this->getProductFields($formData['product_categories'], $formData['tax_rates']);

        // get current logged user's product limits
        $limitsData = $this->getProductsLimitStats($user);

        return view('frontend.products.edit', [
            'product' => $product,
            'productCategories' => $formData['product_categories'],
            'taxRates' => $formData['tax_rates'],
            'fields' => $fields,
            'limitsData' => $limitsData
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
        try {
            $user = Auth::user();

            // Efficient authorization without loading Eloquent model
            $this->productService->authorizeUpdateProduct($user->id, $id);

            $data = $request->validated();

            // Handle image upload if present
            if ($request->hasFile('image')) {
                $data['product_image'] = $request->file('image');
            }

            // Update product using service
            $this->productService->updateProduct($id, $data, $user->id);

            return redirect()->route('frontend.products', ['locale' => app()->getLocale()])
                ->with('success', trans('products.messages.updated'));

        } catch (ValidationException $e) {
            // Handle file upload validation errors with detailed messages
            Log::error('Product update failed', ['product_id' => $id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', trans('products.messages.validation_failed'));
        } catch (\Throwable $e) {
            // Log the actual error for debugging
            Log::error('Product update failed', ['product_id' => $id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()
                ->withInput()
                ->with('error', trans('products.messages.error_update') . ': ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product from storage.
     *
     * @param string $locale
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $locale, int $id)
    {
        $user = Auth::user();

        // Efficient authorization without loading Eloquent model
        $this->productService->authorizeDeleteProduct($user->id, $id);

        // Delete product using service
        $this->productService->deleteProduct($id, Auth::id());

        return redirect()->route('frontend.products', ['locale' => app()->getLocale()])
            ->with('success', trans('products.messages.deleted'));
    }

    /**
     * Get current user's products limits
     *
     * @param \App\Models\User|null $user
     * @return array
     */
    public function getProductsLimitStats($user = null): array
    {
        if ($user === null) {
            $user = Auth::user();
        }
        if ($user) {
            $bestPeriod = $this->limitService->getBestPeriodType($user->id, 'product', 'count');
            $stats = $this->limitService->getUsageStatistics($user->id, 'product', 'count', $bestPeriod);
            $limit = $stats['limit'] ?? (($stats['remaining'] ?? null) !== null ? (int)$stats['remaining'] + (int)($stats['current_usage'] ?? 0) : 0);
            $current = (int)($stats['current_usage'] ?? 0);
            $canCreate = $stats['can_create'] ?? ($limit > $current);
            return [
                'limit' => $limit,
                'current_usage' => $current,
                'allowed' => $canCreate
            ];
        } else {
            return [
                'limit' => 0,
                'current_usage' => 0,
                'allowed' => false
            ];
        }
    }
}
