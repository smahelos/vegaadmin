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
        CRUD::setRoute(config('backpack.base.route_prefix') . '/artisan-command-category');
        CRUD::setEntityNameStrings(__('artisan_commands.categories.entity.singular'), __('artisan_commands.categories.entity.plural'));
    }

    protected function setupListOperation(): void
    {
        CRUD::column('name')->label(__('artisan_commands.categories.fields.name'));
        CRUD::column('slug')->label(__('artisan_commands.categories.fields.slug'));
        CRUD::column('description')->label(__('artisan_commands.categories.fields.description'));
        CRUD::column('commands_count')
            ->label(__('artisan_commands.categories.fields.commands_count'))
            ->type('closure')
            ->function(function ($entry) {
                return $entry->commands->count();
            })
            ->default('0');
        CRUD::column('is_active')->label(__('artisan_commands.categories.fields.is_active'))
            ->type('boolean');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ArtisanCommandCategoryRequest::class);

        CRUD::field('name')
            ->label(__('artisan_commands.categories.fields.name'))
            ->type('text');

        CRUD::field('slug')
            ->label(__('artisan_commands.categories.fields.slug'))
            ->type('text')
            ->hint(__('artisan_commands.categories.hints.slug'));

        CRUD::field('description')
            ->label(__('artisan_commands.categories.fields.description'))
            ->type('textarea');

        CRUD::field('is_active')
            ->label(__('artisan_commands.categories.fields.is_active'))
            ->type('checkbox')
            ->default(true);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
