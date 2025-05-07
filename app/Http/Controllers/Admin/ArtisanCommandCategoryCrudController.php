<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ArtisanCommandCategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ArtisanCommandCategoryCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\ArtisanCommandCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/artisan-command-category');
        CRUD::setEntityNameStrings(__('admin.artisan_commands.category'), __('admin.artisan_commands.categories'));
    }

    protected function setupListOperation()
    {
        CRUD::column('name')->label(__('admin.artisan_commands.fields.name'));
        CRUD::column('slug')->label(__('admin.artisan_commands.fields.slug'));
        CRUD::column('description')->label(__('admin.artisan_commands.fields.description'));
        CRUD::column('commands_count')
            ->label(__('admin.artisan_commands.fields.commands_count'))
            ->type('closure')
            ->function(function($entry) {
                return $entry->commands->count();
            });
        CRUD::column('is_active')->label(__('admin.artisan_commands.fields.is_active'))
            ->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ArtisanCommandCategoryRequest::class);

        CRUD::field('name')
            ->label(__('admin.artisan_commands.fields.name'))
            ->type('text');
        
        CRUD::field('slug')
            ->label(__('admin.artisan_commands.fields.slug'))
            ->type('text')
            ->hint(__('admin.artisan_commands.hints.slug'));
        
        CRUD::field('description')
            ->label(__('admin.artisan_commands.fields.description'))
            ->type('textarea');
        
        CRUD::field('is_active')
            ->label(__('admin.artisan_commands.fields.is_active'))
            ->type('checkbox')
            ->default(true);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
