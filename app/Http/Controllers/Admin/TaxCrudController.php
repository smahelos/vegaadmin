<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Operations\RestrictedDeleteOperation;
use App\Http\Requests\Admin\TaxRequest;
use App\Infrastructure\Forms\Tax\TaxFormFields;
use App\Models\Tax;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

final class TaxCrudController extends CrudController
{
    use CreateOperation;
    use ListOperation;
    use RestrictedDeleteOperation;
    use ShowOperation;
    use TaxFormFields;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(Tax::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/tax',
        );
        CRUD::setEntityNameStrings(
            __('taxes.entity.singular'),
            __('taxes.entity.plural'),
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation(): void
    {
        $column = CRUD::column('name');
        $column->label(
            __('taxes.fields.name'),
        );
        $column = CRUD::column('rate');
        $column->label(
            __('taxes.fields.rate'),
        );
        $column->type('number');
        $column->suffix('%');
        $column = CRUD::column('slug');
        $column->label(
            __('taxes.fields.slug'),
        );
        $column = CRUD::column('description');
        $column->label(
            __('taxes.fields.description'),
        );
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(TaxRequest::class);

        // use the trait method to get the fields
        $this->crud->addFields($this->getTaxFields());
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
