<?php

namespace App\Http\Controllers\Frontend;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Infrastructure\Forms\Products\ProductFormFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    use AuthorizesRequests;
    use ProductFormFields; // Frontend limits handled via observers now

    public function __construct(
        private readonly ProductApplicationServiceInterface $productService,
    ) {}

    /**
     * Display a listing of the user's products.
     */
    public function index(): View
    {
        // we load grid by livewire ProductList component
        return view('frontend.products.index');
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $user = Auth::user();
        $this->productService->authorizeCreateProduct($user->id);

        // Get form data from service
        $formData = $this->productService->getFormData();

        $fields = $this->getProductFields($formData['product_categories'], $formData['tax_rates']);

        return view(
            'frontend.products.create',
            [
                'productCategories' => $formData['product_categories'],
                'taxRates' => $formData['tax_rates'],
                'fields' => $fields,
            ],
        );
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();
        $user = Auth::user();

        // Handle image upload if present
        if ($request->hasFile('image')) {
            $validatedData['image'] = $request->file('image');
        }

        try {
            $product = $this->productService->createProduct($validatedData, $user->id);
            $application = app();
            $locale = $request->route('locale') ?? $application->getLocale();

            $redirector = redirect();
            $redirect = $redirector->route('frontend.products', ['locale' => $locale]);

            return $redirect->with(
                'success',
                trans('products.messages.created'),
            );
        } catch (EntityLimitExceededException $e) {
            // The exception handler answers it with the limit message
            throw $e;
        } catch (ValidationException $e) {
            // Handle file upload validation errors with detailed messages
            $redirector = redirect();
            $redirect = $redirector->back();
            $redirect->withErrors($e->errors());
            $redirect->withInput();

            return $redirect->with(
                'error',
                trans('products.messages.validation_failed'),
            );
        } catch (\Throwable $e) {
            Log::error('Product creation failed', [
                'userId' => Auth::id(),
                'exception' => $e,
            ]);

            $redirector = redirect();
            $redirect = $redirector->back();
            $redirect->withInput();

            return $redirect->with(
                'error',
                __('products.messages.error_create'),
            );
        }
    }

    /**
     * Display the specified product.
     */
    public function show(string $locale, int $id): View
    {
        $user = Auth::user();

        $product = $this->productService->authorizeViewProduct($user->id, $id);

        return view(
            'frontend.products.show',
            compact('product'),
        );
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(string $locale, int $id): View
    {
        $user = Auth::user();

        $product = $this->productService->authorizeUpdateProduct($user->id, $id);

        // Get form data from service
        $formData = $this->productService->getFormData();
        $fields = $this->getProductFields($formData['product_categories'], $formData['tax_rates']);

        return view(
            'frontend.products.edit',
            [
                'product' => $product,
                'productCategories' => $formData['product_categories'],
                'taxRates' => $formData['tax_rates'],
                'fields' => $fields,
            ],
        );
    }

    /**
     * Update the specified product in storage.
     */
    public function update(
        ProductRequest $request,
        string $locale,
        int $id,
    ): RedirectResponse {
        $user = Auth::user();

        // Outside the try: its 404 and 403 must not become the generic error redirect
        $this->productService->authorizeUpdateProduct($user->id, $id);

        try {
            $data = $request->validated();

            // Handle image upload if present
            if ($request->hasFile('image')) {
                $data['product_image'] = $request->file('image');
            }

            // Update product using service
            $this->productService->updateProduct(
                $id,
                $data,
                $user->id,
            );

            $redirector = redirect();
            $application = app();
            $redirect = $redirector->route('frontend.products', ['locale' => $application->getLocale()]);

            return $redirect->with(
                'success',
                trans('products.messages.updated'),
            );

        } catch (ValidationException $e) {
            // Handle file upload validation errors with detailed messages
            Log::error(
                'Product update failed',
                [
                    'product_id' => $id,
                    'user_id' => Auth::id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            );

            $redirector = redirect();
            $redirect = $redirector->back();
            $redirect->withErrors($e->errors());
            $redirect->withInput();

            return $redirect->with(
                'error',
                trans('products.messages.validation_failed'),
            );
        } catch (\Throwable $e) {
            Log::error(
                'Product update failed',
                [
                    'productId' => $id,
                    'userId' => Auth::id(),
                    'exception' => $e,
                ],
            );

            $redirector = redirect();
            $redirect = $redirector->back();
            $redirect->withInput();

            return $redirect->with(
                'error',
                __('products.messages.error_update'),
            );
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(string $locale, int $id): RedirectResponse
    {
        $user = Auth::user();

        // Efficient authorization without loading Eloquent model
        $this->productService->authorizeDeleteProduct($user->id, $id);

        // Delete product using service
        $this->productService->deleteProduct($id, Auth::id());

        $redirector = redirect();
        $application = app();
        $redirect = $redirector->route('frontend.products', ['locale' => $application->getLocale()]);

        return $redirect->with(
            'success',
            trans('products.messages.deleted'),
        );
    }
}
