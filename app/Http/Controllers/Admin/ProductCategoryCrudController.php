<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Operations\RestrictedDeleteOperation;
use App\Http\Requests\Admin\ProductCategoryRequest;
use App\Models\ProductCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ProductCategoryCrudController extends CrudController
{
    use CreateOperation;
    use ListOperation;
    use RestrictedDeleteOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(ProductCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/product-category');
        CRUD::setEntityNameStrings(
            __('products.categories.entity.singular'),
            __('products.categories.entity.plural'),
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation(): void
    {
        CRUD::column('name')->label(__('products.categories.fields.name'));
        CRUD::column('slug')->label(__('products.categories.fields.slug'));
        CRUD::column('description')->label(__('products.categories.fields.description'));
        CRUD::column('created_at')->label(__('products.categories.fields.created_at'));
        CRUD::column('updated_at')->label(__('products.categories.fields.updated_at'));
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ProductCategoryRequest::class);

        CRUD::field('name')->label(__('products.categories.fields.name'));
        CRUD::field('slug')
            ->label(__('products.categories.fields.slug'))
            ->hint(__('products.categories.hints.slug'));
        CRUD::field('description')
            ->type('textarea')
            ->label(__('products.categories.fields.description'));
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
