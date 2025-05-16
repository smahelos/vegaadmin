<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProductRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Services\ProductsService;
use App\Services\TaxesService;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ProductCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(\App\Models\Product::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/product');
        CRUD::setEntityNameStrings(
            trans('admin.products.product'),
            trans('admin.products.products')
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation(): void
    {
        CRUD::column('name');
        CRUD::column('price');
        CRUD::column('user_id')
            ->type('integer')
            ->label('Owner')
            ->entity('user')
            ->attribute('name');
        CRUD::column('category_id')->type('select_from_array');
        CRUD::column('tax_id')->type('select_from_array');
        CRUD::column('is_default')->type('boolean');
        CRUD::column('created_at');
        CRUD::column('updated_at');
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ProductRequest::class);

        CRUD::field('name');
        CRUD::field('slug')->hint(trans('admin.products.leave_empty_for_autogeneration'));
        
        CRUD::field('user_id')
        ->type('hidden')
        ->label('Owner')
        ->entity('user')
        ->attribute('name')
        ->options(function ($query) {
            return $query->orderBy('name', 'ASC')->get();
        });
        
        CRUD::field('supplier_id')
            ->type('select')
            ->label(trans('admin.products.supplier'))
            ->entity('supplier')
            ->model('App\Models\Supplier')
            ->attribute('name')
            ->options(function ($query) {
                return $query->orderBy('name', 'ASC')->get();
            });
        
        CRUD::field('price')->type('number')->attributes(['step' => '0.01']);
        // Get taxes from the service
        $taxesService = new TaxesService();
        // Prepare taxes for select form
        $taxes = $taxesService->getAllTaxesForSelect();
            
        CRUD::field('tax_id')
            ->type('select_from_array')
            ->label(trans('admin.products.tax'))
            ->entity('tax')
            ->model('App\Models\Tax')
            ->attribute('name')
            ->options($taxes);

        // Get product categories from the service
        $productsService = new ProductsService();
        // Prepare product categories for select form
        $productCategories = $productsService->getAllCategories();

        CRUD::field('category_id')
            ->type('select_from_array')
            ->label(trans('admin.products.category'))
            ->entity('category')
            ->model('App\Models\ProductCategory')
            ->pivot(true)
            ->attribute('name')
            ->options($productCategories);
        CRUD::field('description')->type('textarea');
        CRUD::field('is_default')->type('checkbox');
        CRUD::field('is_active')->type('checkbox');

        CRUD::field('image')
            ->type('image_preview')
            ->label(trans('admin.products.image'))
            ->upload(true)
            ->disk('public')
            ->prefix('storage/')
            ->hint(trans('admin.products.image_help'))
            ->uploadRoute('backpack.upload')
            ->wrapper([
                'class' => 'form-group col-md-6'
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
            </script>'
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
