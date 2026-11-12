<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Product\Commands\CreateProduct;
use App\Application\Product\Commands\DeleteProduct;
use App\Application\Product\Commands\UpdateProduct;
use App\Application\Product\Contracts\ProductFormDataServiceInterface;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\Exceptions\ProductInUseException;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Tax\Contracts\TaxServiceInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\ProductRequest;
use App\Infrastructure\Forms\User\EntityUsageFormFields;
use App\Models\Product;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\Facades\Alert;

final class ProductCrudController extends CrudController
{
    use ActsAsAdmin;
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use NarrowsCrudTypes;
    use ShowOperation;
    use UpdateOperation;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
        private readonly ProductFormDataServiceInterface $formData,
        private readonly TaxServiceInterface $taxesService,
    ) {
        parent::__construct();
        $throttle = $this->middleware('throttle:create');
        $throttle->only('store');
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(Product::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/product',
        );
        CRUD::setEntityNameStrings(
            __('products.entity.singular'),
            __('products.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => Permission::PRODUCT_VIEW,
                'show' => Permission::PRODUCT_VIEW,
                'create' => Permission::PRODUCT_CREATE,
                'update' => Permission::PRODUCT_UPDATE,
                'delete' => Permission::PRODUCT_DELETE,
            ],
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation(): void
    {
        // Add filters
        CRUD::addFilter(
            [
                'type' => 'simple',
                'name' => 'is_active',
                'label' => __('products.filters.active_only'),
            ],
            false,
            function (): void {
                CRUD::addClause(
                    'where',
                    'is_active',
                    '1',
                );
            },
        );

        $filter = CRUD::filter('user_id');
        $filter->type('select2');
        $filter->label(
            __('products.fields.user_id'),
        );
        $userNames = User::pluck('name', 'id');
        $filter->values($userNames->toArray());
        $filter->whenActive(
            function ($value): void {
                CRUD::addClause(
                    'where',
                    'user_id',
                    $value,
                );
            },
        );

        $column = CRUD::column('user_id');
        $column->type('integer');
        $column->label(
            __('products.fields.user_id'),
        );
        $column->entity('user');
        $column->attribute('name');
        $column->value(
            function ($entry) {
                return $entry->user_id ? $entry->user->name : __('general.placeholders.not_available');
            },
        );

        $column = CRUD::column('name');
        $column->label(
            __('products.fields.name'),
        );
        $column = CRUD::column('price');
        $column->label(
            __('products.fields.price'),
        );
        $column = CRUD::column('category_id');
        $column->type('select_from_array');
        $column->label(
            __('products.fields.category_id'),
        );
        $column->value(
            function ($entry) {
                return $entry->category ? $entry->category->name : __('general.placeholders.not_available');
            },
        );
        $column = CRUD::column('tax_id');
        $column->type('select_from_array');
        $column->label(
            __('products.fields.tax_id'),
        );
        $column->value(
            function ($entry) {
                return $entry->tax ? $entry->tax->name : __('general.placeholders.not_available');
            },
        );
        $column = CRUD::column('is_default');
        $column->type('boolean');
        $column->label(
            __('products.fields.is_default'),
        );
        $column = CRUD::column('created_at');
        $column->label(
            __('products.fields.created_at'),
        );
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ProductRequest::class);

        $field = CRUD::field('name');
        $field->label(
            __('products.fields.name'),
        );
        $field = CRUD::field('slug');
        $field->label(
            __('products.fields.slug'),
        );
        $field->hint(
            __('products.hints.slug'),
        );

        // The owner's plan limits the products, so the admin picks whose catalogue the product joins
        $field = CRUD::field('user_id');
        $field->type('select2');
        $field->label(
            __('products.fields.user_id'),
        );
        $field->entity('user');
        $field->model(User::class);
        $field->attribute('name');
        $field->allows_null(false);
        CRUD::addField(
            EntityUsageFormFields::field(LimitedEntity::PRODUCT),
        );

        $field = CRUD::field('price');
        $field->type('number');
        $field->label(
            __('products.fields.price'),
        );
        $field->attributes(['step' => '0.01']);

        $currencyCodes = array_column(Currency::cases(), 'value');
        $field = CRUD::field('currency');
        $field->type('select_from_array');
        $field->label(
            __('products.fields.currency'),
        );
        $field->options(
            array_combine($currencyCodes, $currencyCodes),
        );
        $field->allows_null(false);

        // Get taxes from the service
        $taxes = $this->taxesService->getAllTaxesForSelect();

        $field = CRUD::field('tax_id');
        $field->type('select_from_array');
        $field->label(
            __('products.fields.tax_id'),
        );
        $field->entity('tax');
        $field->model('App\Models\Tax');
        $field->attribute('name');
        $field->options($taxes);

        $productCategories = $this->formData->getAllCategories();

        $field = CRUD::field('category_id');
        $field->type('select_from_array');
        $field->label(
            __('products.fields.category_id'),
        );
        $field->entity('category');
        $field->model('App\Models\ProductCategory');
        $field->pivot(true);
        $field->attribute('name');
        $field->options($productCategories);
        $field = CRUD::field('description');
        $field->type('textarea');
        $field->label(
            __('products.fields.description'),
        );
        $field = CRUD::field('is_default');
        $field->type('checkbox');
        $field->label(
            __('products.fields.is_default'),
        );
        $field = CRUD::field('is_active');
        $field->type('checkbox');
        $field->label(
            __('products.fields.is_active'),
        );

        $field = CRUD::field('image');
        $field->type('image_with_preview');
        $field->label(
            __('products.fields.image'),
        );
        $field->upload(true);
        $field->hint(
            __('products.hints.image'),
        );
        $field->wrapper([
            'class' => 'form-group col-md-6',
        ]);

        // Add JavaScript for better handling of the custom field
        $this->crud->addField(
            [
                'name' => 'image_preview_scripts',
                'type' => 'custom_html',
                'value' => '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    // This ensures the image preview is updated when form is submitted
                    document.querySelector("form").addEventListener("submit", function() {
                        const removeCheckbox = document.getElementById("image_remove");
                        const inputField = document.getElementById("image_input");

                        if (removeCheckbox && removeCheckbox.checked && inputField.files.length === 0) {
                            // If remove is checked but no new file is selected
                            const hiddenInput = document.createElement("input");
                            hiddenInput.type = "hidden";
                            hiddenInput.name = "image";
                            hiddenInput.value = "";
                            this.appendChild(hiddenInput);
                        }
                    });
                });
            </script>',
            ],
        );
    }

    /**
     * Moving a product to another owner would bypass that owner's limit, so the owner is fixed after creation:
     * the hidden field only feeds the validation and the usage shown on the form, UpdateProduct keeps the owner.
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
        $field = CRUD::field('user_id');
        $field->type('hidden');
    }

    public function store(): JsonResponse|RedirectResponse
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->validatedRequest(ProductRequest::class);
        $product = $this->useCases->run(
            $this->admin(),
            new CreateProduct(
                UserId::fromInt(
                    (int) $request->validated('user_id'),
                ),
                $request->toWriteData(),
            ),
        );

        return $this->saved(
            __('products.messages.created'),
            $product->id,
        );
    }

    public function update(): JsonResponse|RedirectResponse
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->validatedRequest(ProductRequest::class);
        $entry = $this->currentEntry(Product::class);
        $product = $this->useCases->run(
            $this->admin(),
            new UpdateProduct(
                UserId::fromInt($entry->user_id),
                ProductId::fromInt($entry->id),
                $request->toWriteData(),
            ),
        );

        return $this->saved(
            __('products.messages.updated'),
            $product->id,
        );
    }

    /**
     * @return string|array{error: list<string>} "1" when deleted; Backpack's delete button shows the notification
     */
    public function destroy(int|string $id): string|array
    {
        $this->crud->hasAccessOrFail('delete');
        $entry = $this->currentEntry(Product::class);
        try {
            $this->useCases->run(
                $this->admin(),
                new DeleteProduct(
                    UserId::fromInt($entry->user_id),
                    ProductId::fromInt($entry->id),
                ),
            );
        } catch (ProductInUseException) {
            return ['error' => [__('products.messages.error_delete_invoices')]];
        }

        return '1';
    }

    private function saved(string $message, int $id): JsonResponse|RedirectResponse
    {
        $alerts = Alert::success($message);
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($id);
    }
}
