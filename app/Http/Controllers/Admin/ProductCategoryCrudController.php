<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProductCategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ProductCategoryCrudController extends CrudController
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
        CRUD::setModel(\App\Models\ProductCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/product-category');
        CRUD::setEntityNameStrings(
            trans('admin.product_category'),
            trans('admin.product_categories')
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation(): void
    {
        CRUD::column('name');
        CRUD::column('slug');
        CRUD::column('description');
        CRUD::column('created_at');
        CRUD::column('updated_at');
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ProductCategoryRequest::class);

        CRUD::field('name');
        CRUD::field('slug')->hint(trans('admin.leave_empty_for_autogeneration'));
        CRUD::field('description')->type('textarea');
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
