<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Console\Contracts\ArtisanCommandsServiceInterface;
use App\Http\Requests\Admin\ArtisanCommandRequest;
use App\Models\ArtisanCommand;
use App\Models\ArtisanCommandCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ArtisanCommandCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    private ArtisanCommandsServiceInterface $commandsService;

    public function __construct(ArtisanCommandsServiceInterface $commandsService)
    {
        parent::__construct();
        $this->commandsService = $commandsService;
    }

    public function setup(): void
    {
        CRUD::setModel(ArtisanCommand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/artisan-command');
        CRUD::setEntityNameStrings(__('artisan_commands.entity.singular'), __('artisan_commands.entity.plural'));
    }

    protected function setupListOperation(): void
    {
        CRUD::column('name')->label(__('artisan_commands.fields.name'));
        CRUD::column('command')->label(__('artisan_commands.fields.command'));
        CRUD::column('category_id')
            ->label(__('artisan_commands.fields.category_id'))
            ->type('select')
            ->entity('category')
            ->attribute('name')
            ->model('App\Models\ArtisanCommandCategory');
        CRUD::column('description')->label(__('artisan_commands.fields.description'));
        CRUD::column('is_active')->label(__('artisan_commands.fields.is_active'))
            ->type('boolean');
        CRUD::column('sort_order')->label(__('artisan_commands.fields.sort_order'));
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ArtisanCommandRequest::class);

        // Get available commands from the ArtisanCommandsService
        // and filter them to show only those that are not already in the database
        // This is done to avoid duplicates and ensure that the user can only select from available commands
        // that are not already registered in the database.
        $availableCommands = $this->commandsService->getAllCommands(true);

        CRUD::field('name')
            ->label(__('artisan_commands.fields.name'))
            ->type('text');

        CRUD::field('command')
            ->label(__('artisan_commands.fields.command'))
            ->type('select_from_array')
            ->options($availableCommands)
            ->allows_null(false)
            ->hint(__('artisan_commands.hints.command'));

        $categories = ArtisanCommandCategory::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        CRUD::field('category_id')
            ->label(__('artisan_commands.fields.category_id'))
            ->type('select_from_array')
            ->options($categories)
            ->allows_null(false);

        CRUD::field('description')
            ->label(__('artisan_commands.fields.description'))
            ->type('textarea');

        CRUD::field('parameters_description')
            ->label(__('artisan_commands.fields.parameters_description'))
            ->type('textarea')
            ->hint(__('artisan_commands.hints.parameters_description'));

        CRUD::field('is_active')
            ->label(__('artisan_commands.fields.is_active'))
            ->type('checkbox')
            ->default(true);

        CRUD::field('sort_order')
            ->label(__('artisan_commands.fields.sort_order'))
            ->type('number')
            ->default(0)
            ->hint(__('artisan_commands.hints.sort_order'));
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
