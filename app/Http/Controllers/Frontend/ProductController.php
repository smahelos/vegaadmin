<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Product\Commands\CreateProduct;
use App\Application\Product\Commands\DeleteProduct;
use App\Application\Product\Commands\UpdateProduct;
use App\Application\Product\Contracts\ProductFormDataServiceInterface;
use App\Application\Product\Queries\GetProduct;
use App\Application\Product\Queries\GetProductForEdit;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Infrastructure\Forms\Products\ProductFormFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class ProductController extends Controller
{
    use ProductFormFields;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
        private readonly ProductFormDataServiceInterface $formData,
    ) {}

    /**
     * The list is the ProductList component, which runs its own Query.
     */
    public function index(): View
    {
        return view('frontend.products.index');
    }

    /**
     * The route answers a user who may not create products with a 403 (allowed:CreateProduct).
     */
    public function create(): View
    {
        $formData = $this->formData->getFormData();

        return view(
            'frontend.products.create',
            [
                'productCategories' => $formData['product_categories'],
                'taxRates' => $formData['tax_rates'],
                'fields' => $this->getProductFields($formData['product_categories'], $formData['tax_rates']),
            ],
        );
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $owner = $this->owner();
        // The route names the same use case in its `allowed` middleware: change them together
        $this->useCases->run(
            Actor::user($owner),
            new CreateProduct($owner, $request->toWriteData()),
        );

        return $this->toList(
            __('products.messages.created'),
        );
    }

    public function show(string $locale, int $id): View
    {
        $owner = $this->owner();
        $product = $this->useCases->run(
            Actor::user($owner),
            new GetProduct(
                $owner,
                ProductId::fromInt($id),
            ),
        );

        return view(
            'frontend.products.show',
            ['product' => $product],
        );
    }

    public function edit(string $locale, int $id): View
    {
        $owner = $this->owner();
        $product = $this->useCases->run(
            Actor::user($owner),
            new GetProductForEdit(
                $owner,
                ProductId::fromInt($id),
            ),
        );
        $formData = $this->formData->getFormData();

        return view(
            'frontend.products.edit',
            [
                'product' => $product,
                'productCategories' => $formData['product_categories'],
                'taxRates' => $formData['tax_rates'],
                'fields' => $this->getProductFields($formData['product_categories'], $formData['tax_rates']),
            ],
        );
    }

    public function update(
        ProductRequest $request,
        string $locale,
        int $id,
    ): RedirectResponse {
        $owner = $this->owner();
        // The route names the same use case in its `allowed` middleware: change them together
        $this->useCases->run(
            Actor::user($owner),
            new UpdateProduct(
                $owner,
                ProductId::fromInt($id),
                $request->toWriteData(),
            ),
        );

        return $this->toList(
            __('products.messages.updated'),
        );
    }

    public function destroy(string $locale, int $id): RedirectResponse
    {
        $owner = $this->owner();
        $this->useCases->run(
            Actor::user($owner),
            new DeleteProduct(
                $owner,
                ProductId::fromInt($id),
            ),
        );

        return $this->toList(
            __('products.messages.deleted'),
        );
    }

    private function owner(): UserId
    {
        return UserId::fromInt((int) Auth::id());
    }

    private function toList(string $message): RedirectResponse
    {
        $redirector = redirect();
        $redirect = $redirector->route('frontend.products', ['locale' => App::currentLocale()]);

        return $redirect->with('success', $message);
    }
}
