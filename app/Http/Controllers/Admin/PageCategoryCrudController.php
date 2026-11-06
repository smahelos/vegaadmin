<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PageCategoryRequest;
use App\Models\PageCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PageCategoryCrudController
 *
 * @property-read CrudPanel $crud
 */
class PageCategoryCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(PageCategory::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/page-category',
        );
        CRUD::setEntityNameStrings(
            __('pages.categories.entity.singular'),
            __('pages.categories.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => 'can_view_page',
                'show' => 'can_view_page',
                'create' => 'can_create_page',
                'update' => 'can_update_page',
                'delete' => 'can_delete_page',
            ],
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     */
    protected function setupListOperation(): void
    {
        $column = CRUD::column('name');
        $column->label(
            __('pages.categories.fields.name'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                $application = app();
                $currentLocale = $application->getLocale();

                return $entry->getName($currentLocale) ?? $entry->getName('cs') ?? __('general.placeholders.not_available');
            },
        );

        $column = CRUD::column('slug');
        $column->label(
            __('pages.categories.fields.slug'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                $application = app();
                $currentLocale = $application->getLocale();

                return $entry->getSlug($currentLocale) ?? $entry->getSlug('cs') ?? __('general.placeholders.not_available');
            },
        );

        $column = CRUD::column('description');
        $column->label(
            __('pages.categories.fields.description'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                $application = app();
                $currentLocale = $application->getLocale();
                $description = $entry->getDescription($currentLocale) ?? $entry->getDescription('cs') ?? '';

                return \Str::limit($description, 100);
            },
        );

        $column = CRUD::column('pages_count');
        $column->label(
            __('pages.categories.fields.pages_count'),
        );
        $column->type('closure');
        $column->function(function ($entry) {
            return $entry->pages_count;
        });
        $column->default('0');

        $column = CRUD::column('created_at');
        $column->label(
            __('pages.categories.fields.created_at'),
        );
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(PageCategoryRequest::class);

        CRUD::addField(
            [
                'name' => 'name',
                'label' => __('pages.categories.fields.name'),
                'type' => 'multilingual',
                'hint' => __('pages.categories.hints.name'),
            ],
        );

        CRUD::addField(
            [
                'name' => 'slug',
                'label' => __('pages.categories.fields.slug'),
                'type' => 'multilingual',
                'hint' => __('pages.categories.hints.slug'),
            ],
        );

        CRUD::addField(
            [
                'name' => 'description',
                'label' => __('pages.categories.fields.description'),
                'type' => 'multilingual',
                'field_type' => 'textarea',
                'attributes' => ['rows' => 4],
            ],
        );
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    /**
     * Define what happens when the Show operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-show
     */
    protected function setupShowOperation(): void
    {
        // Use same columns as list operation but with more detail
        $this->setupListOperation();

        // Add more detailed columns for show view
        $column = CRUD::column('created_at');
        $column->label(
            __('pages.categories.fields.created_at'),
        );
        $column->type('datetime');

        $column = CRUD::column('updated_at');
        $column->label(
            __('pages.categories.fields.updated_at'),
        );
        $column->type('datetime');
    }
}
