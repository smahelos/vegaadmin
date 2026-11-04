<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Operations\RestrictedDeleteOperation;
use App\Http\Requests\Admin\ArtisanCommandCategoryRequest;
use App\Models\ArtisanCommandCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ArtisanCommandCategoryCrudController extends CrudController
{
    use CreateOperation;
    use ListOperation;
    use RestrictedDeleteOperation;
    use ShowOperation;
    use UpdateOperation;

    public function setup(): void
    {
        CRUD::setModel(ArtisanCommandCategory::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/artisan-command-category',
        );
        CRUD::setEntityNameStrings(
            __('artisan_commands.categories.entity.singular'),
            __('artisan_commands.categories.entity.plural'),
        );
    }

    protected function setupListOperation(): void
    {
        $column = CRUD::column('name');
        $column->label(
            __('artisan_commands.categories.fields.name'),
        );
        $column = CRUD::column('slug');
        $column->label(
            __('artisan_commands.categories.fields.slug'),
        );
        $column = CRUD::column('description');
        $column->label(
            __('artisan_commands.categories.fields.description'),
        );
        $column = CRUD::column('commands_count');
        $column->label(
            __('artisan_commands.categories.fields.commands_count'),
        );
        $column->type('closure');
        $column->function(function ($entry) {
            return $entry->commands->count();
        });
        $column->default('0');
        $column = CRUD::column('is_active');
        $column->label(
            __('artisan_commands.categories.fields.is_active'),
        );
        $column->type('boolean');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ArtisanCommandCategoryRequest::class);

        $field = CRUD::field('name');
        $field->label(
            __('artisan_commands.categories.fields.name'),
        );
        $field->type('text');

        $field = CRUD::field('slug');
        $field->label(
            __('artisan_commands.categories.fields.slug'),
        );
        $field->type('text');
        $field->hint(
            __('artisan_commands.categories.hints.slug'),
        );

        $field = CRUD::field('description');
        $field->label(
            __('artisan_commands.categories.fields.description'),
        );
        $field->type('textarea');

        $field = CRUD::field('is_active');
        $field->label(
            __('artisan_commands.categories.fields.is_active'),
        );
        $field->type('checkbox');
        $field->default(true);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
