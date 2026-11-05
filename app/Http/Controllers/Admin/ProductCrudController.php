<?php

namespace App\Http\Controllers\Admin;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Tax\Contracts\TaxServiceInterface;
use App\Http\Controllers\Admin\Operations\RestrictedDeleteOperation;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ProductCrudController extends CrudController
{
    use CreateOperation {
        store as traitStore;
    }
    use ListOperation;
    use RestrictedDeleteOperation;
    use ShowOperation;
    use UpdateOperation; // Usage recording via generic observer

    private ProductApplicationServiceInterface $productService;

    private TaxServiceInterface $taxesService;

    public function __construct(
        ProductApplicationServiceInterface $productService,
        TaxServiceInterface $taxesService,
    ) {
        parent::__construct();
        $this->productService = $productService;
        $this->taxesService = $taxesService;
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
                return $entry->user_id ? $entry->user->name : 'N/A';
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
                return $entry->category ? $entry->category->name : 'N/A';
            },
        );
        $column = CRUD::column('tax_id');
        $column->type('select_from_array');
        $column->label(
            __('products.fields.tax_id'),
        );
        $column->value(
            function ($entry) {
                return $entry->tax ? $entry->tax->name : 'N/A';
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
        // CRUD::column('updated_at');
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

        $field = CRUD::field('user_id');
        $field->type('hidden');
        $field->label(
            __('products.fields.user_id'),
        );
        $field->entity('user');
        $field->attribute('name');
        $field->options(
            function ($query) {
                $query->orderBy('name', 'ASC');

                return $query->get();
            },
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

        // Get product categories from the service
        $productCategories = $this->productService->getAllCategories();

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
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    /**
     * Ensure user_id is always set to the current backpack user before storing.
     */
    protected function beforeEntityStore(): void
    {
        if (backpack_user()) {
            $request = $this->crud->getRequest();
            if (!$request->has('user_id') || empty($request->input('user_id'))) {
                $request->merge(['user_id' => backpack_user()->id]);
            }
        }
    }

    /**
     * Override store only for user assignment; usage recorded by ProductObserver.
     */
    public function store(): JsonResponse|RedirectResponse
    {
        $this->beforeEntityStore();

        return $this->traitStore();
    }
}
