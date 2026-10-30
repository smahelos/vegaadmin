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
        CRUD::setRoute(config('backpack.base.route_prefix') . '/product');
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

        CRUD::filter('user_id')
            ->type('select2')
            ->label(__('products.fields.user_id'))
            ->values(
                User::pluck('name', 'id')->toArray(),
            )
            ->whenActive(function ($value): void {
                CRUD::addClause(
                    'where',
                    'user_id',
                    $value,
                );
            });

        CRUD::column('user_id')
            ->type('integer')
            ->label(__('products.fields.user_id'))
            ->entity('user')
            ->attribute('name')
            ->value(function ($entry) {
                return $entry->user_id ? $entry->user->name : 'N/A';
            });

        CRUD::column('name')->label(__('products.fields.name'));
        CRUD::column('price')->label(__('products.fields.price'));
        CRUD::column('category_id')
            ->type('select_from_array')
            ->label(__('products.fields.category_id'))
            ->value(function ($entry) {
                return $entry->category ? $entry->category->name : 'N/A';
            });
        CRUD::column('tax_id')
            ->type('select_from_array')
            ->label(__('products.fields.tax_id'))
            ->value(function ($entry) {
                return $entry->tax ? $entry->tax->name : 'N/A';
            });
        CRUD::column('is_default')->type('boolean')->label(__('products.fields.is_default'));
        CRUD::column('created_at')->label(__('products.fields.created_at'));
        // CRUD::column('updated_at');
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ProductRequest::class);

        CRUD::field('name')->label(__('products.fields.name'));
        CRUD::field('slug')
            ->label(__('products.fields.slug'))
            ->hint(__('products.hints.slug'));

        CRUD::field('user_id')
            ->type('hidden')
            ->label(__('products.fields.user_id'))
            ->entity('user')
            ->attribute('name')
            ->options(function ($query) {
                return $query->orderBy('name', 'ASC')->get();
            });

        CRUD::field('price')
            ->type('number')
            ->label(__('products.fields.price'))
            ->attributes(['step' => '0.01']);

        $currencyCodes = array_column(Currency::cases(), 'value');
        CRUD::field('currency')
            ->type('select_from_array')
            ->label(__('products.fields.currency'))
            ->options(array_combine($currencyCodes, $currencyCodes))
            ->allows_null(false);

        // Get taxes from the service
        $taxes = $this->taxesService->getAllTaxesForSelect();

        CRUD::field('tax_id')
            ->type('select_from_array')
            ->label(__('products.fields.tax_id'))
            ->entity('tax')
            ->model('App\Models\Tax')
            ->attribute('name')
            ->options($taxes);

        // Get product categories from the service
        $productCategories = $this->productService->getAllCategories();

        CRUD::field('category_id')
            ->type('select_from_array')
            ->label(__('products.fields.category_id'))
            ->entity('category')
            ->model('App\Models\ProductCategory')
            ->pivot(true)
            ->attribute('name')
            ->options($productCategories);
        CRUD::field('description')->type('textarea')->label(__('products.fields.description'));
        CRUD::field('is_default')->type('checkbox')->label(__('products.fields.is_default'));
        CRUD::field('is_active')->type('checkbox')->label(__('products.fields.is_active'));

        CRUD::field('image')
            ->type('image_with_preview')
            ->label(__('products.fields.image'))
            ->upload(true)
            // ->disk('public')
            // ->prefix('storage/')
            // ->uploadRoute('backpack.upload')
            ->hint(__('products.hints.image'))
            ->wrapper([
                'class' => 'form-group col-md-6',
            ]);

        // Add JavaScript for better handling of the custom field
        $this->crud->addField([
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
        ]);
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
            if (! $request->has('user_id') || empty($request->input('user_id'))) {
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
