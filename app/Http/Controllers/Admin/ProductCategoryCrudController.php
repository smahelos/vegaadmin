<?php

declare(strict_types=1);

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

final class ProductCategoryCrudController extends CrudController
{
    use CreateOperation;
    use GatesCrudOperations;
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
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/product-category',
        );
        CRUD::setEntityNameStrings(
            __('products.categories.entity.singular'),
            __('products.categories.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => 'can_view_product',
                'show' => 'can_view_product',
                'create' => 'can_create_product',
                'update' => 'can_update_product',
                'delete' => 'can_delete_product',
            ],
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation(): void
    {
        $column = CRUD::column('name');
        $column->label(
            __('products.categories.fields.name'),
        );
        $column = CRUD::column('slug');
        $column->label(
            __('products.categories.fields.slug'),
        );
        $column = CRUD::column('description');
        $column->label(
            __('products.categories.fields.description'),
        );
        $column = CRUD::column('created_at');
        $column->label(
            __('products.categories.fields.created_at'),
        );
        $column = CRUD::column('updated_at');
        $column->label(
            __('products.categories.fields.updated_at'),
        );
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ProductCategoryRequest::class);

        $field = CRUD::field('name');
        $field->label(
            __('products.categories.fields.name'),
        );
        $field = CRUD::field('slug');
        $field->label(
            __('products.categories.fields.slug'),
        );
        $field->hint(
            __('products.categories.hints.slug'),
        );
        $field = CRUD::field('description');
        $field->type('textarea');
        $field->label(
            __('products.categories.fields.description'),
        );
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
