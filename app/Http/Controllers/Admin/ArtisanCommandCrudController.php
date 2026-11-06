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
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/artisan-command',
        );
        CRUD::setEntityNameStrings(
            __('artisan_commands.entity.singular'),
            __('artisan_commands.entity.plural'),
        );
    }

    protected function setupListOperation(): void
    {
        $column = CRUD::column('name');
        $column->label(
            __('artisan_commands.fields.name'),
        );
        $column = CRUD::column('command');
        $column->label(
            __('artisan_commands.fields.command'),
        );
        $column = CRUD::column('category_id');
        $column->label(
            __('artisan_commands.fields.category_id'),
        );
        $column->type('select');
        $column->entity('category');
        $column->attribute('name');
        $column->model('App\Models\ArtisanCommandCategory');
        $column = CRUD::column('description');
        $column->label(
            __('artisan_commands.fields.description'),
        );
        $column = CRUD::column('is_active');
        $column->label(
            __('artisan_commands.fields.is_active'),
        );
        $column->type('boolean');
        $column = CRUD::column('sort_order');
        $column->label(
            __('artisan_commands.fields.sort_order'),
        );
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ArtisanCommandRequest::class);

        // Get available commands from the ArtisanCommandsService
        // and filter them to show only those that are not already in the database
        // This is done to avoid duplicates and ensure that the user can only select from available commands
        // that are not already registered in the database.
        $availableCommands = $this->commandsService->getAllCommands(true);

        $field = CRUD::field('name');
        $field->label(
            __('artisan_commands.fields.name'),
        );
        $field->type('text');

        $field = CRUD::field('command');
        $field->label(
            __('artisan_commands.fields.command'),
        );
        $field->type('select_from_array');
        $field->options($availableCommands);
        $field->allows_null(false);
        $field->hint(
            __('artisan_commands.hints.command'),
        );

        $categoryQuery = ArtisanCommandCategory::where('is_active', true);
        $categoryQuery->orderBy('name');
        $categoryNames = $categoryQuery->pluck('name', 'id');
        $categories = $categoryNames->toArray();

        $field = CRUD::field('category_id');
        $field->label(
            __('artisan_commands.fields.category_id'),
        );
        $field->type('select_from_array');
        $field->options($categories);
        $field->allows_null(false);

        $field = CRUD::field('description');
        $field->label(
            __('artisan_commands.fields.description'),
        );
        $field->type('textarea');

        $field = CRUD::field('parameters_description');
        $field->label(
            __('artisan_commands.fields.parameters_description'),
        );
        $field->type('textarea');
        $field->hint(
            __('artisan_commands.hints.parameters_description'),
        );

        $field = CRUD::field('is_active');
        $field->label(
            __('artisan_commands.fields.is_active'),
        );
        $field->type('checkbox');
        $field->default(true);

        $field = CRUD::field('sort_order');
        $field->label(
            __('artisan_commands.fields.sort_order'),
        );
        $field->type('number');
        $field->default(0);
        $field->hint(
            __('artisan_commands.hints.sort_order'),
        );
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
