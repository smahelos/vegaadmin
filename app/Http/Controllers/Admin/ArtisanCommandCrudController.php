<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ArtisanCommandRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Services\ArtisanCommandsService;

class ArtisanCommandCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\ArtisanCommand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/artisan-command');
        CRUD::setEntityNameStrings(__('admin.artisan_commands.command'), __('admin.artisan_commands.commands'));
    }

    protected function setupListOperation()
    {
        CRUD::column('name')->label(__('admin.artisan_commands.fields.name'));
        CRUD::column('command')->label(__('admin.artisan_commands.fields.command'));
        CRUD::column('category_id')
            ->label(__('admin.artisan_commands.fields.category'))
            ->type('select')
            ->entity('category')
            ->attribute('name')
            ->model('App\Models\ArtisanCommandCategory');
        CRUD::column('description')->label(__('admin.artisan_commands.fields.description'));
        CRUD::column('is_active')->label(__('admin.artisan_commands.fields.is_active'))
            ->type('boolean');
        CRUD::column('sort_order')->label(__('admin.artisan_commands.fields.sort_order'));
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ArtisanCommandRequest::class);
        
        // Získáme všechny dostupné příkazy
        $commandsService = new ArtisanCommandsService();
        $availableCommands = $commandsService->getAllCommands(true);

        CRUD::field('name')
            ->label(__('admin.artisan_commands.fields.name'))
            ->type('text');
        
        CRUD::field('command')
            ->label(__('admin.artisan_commands.fields.command'))
            ->type('select_from_array')
            ->options($availableCommands)
            ->allows_null(false)
            ->hint(__('admin.artisan_commands.hints.command'));
        
        $categories = \App\Models\ArtisanCommandCategory::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
        
        CRUD::field('category_id')
            ->label(__('admin.artisan_commands.fields.category'))
            ->type('select_from_array')
            ->options($categories)
            ->allows_null(false);
        
        CRUD::field('description')
            ->label(__('admin.artisan_commands.fields.description'))
            ->type('textarea');
        
        CRUD::field('parameters_description')
            ->label(__('admin.artisan_commands.fields.parameters_description'))
            ->type('textarea')
            ->hint(__('admin.artisan_commands.hints.parameters_description'));
        
        CRUD::field('is_active')
            ->label(__('admin.artisan_commands.fields.is_active'))
            ->type('checkbox')
            ->default(true);
        
        CRUD::field('sort_order')
            ->label(__('admin.artisan_commands.fields.sort_order'))
            ->type('number')
            ->default(0)
            ->hint(__('admin.artisan_commands.hints.sort_order'));
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
