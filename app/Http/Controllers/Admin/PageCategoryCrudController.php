<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PageCategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PageCategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PageCategoryCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\PageCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/page-category');
        CRUD::setEntityNameStrings(
            trans('admin.page_categories.page_category'),
            trans('admin.page_categories.page_categories')
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')
            ->label(trans('admin.page_categories.name'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();
                return $entry->getName($currentLocale) ?? $entry->getName('cs') ?? 'N/A';
            });
        
        CRUD::column('slug')
            ->label(trans('admin.page_categories.slug'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();
                return $entry->getSlug($currentLocale) ?? $entry->getSlug('cs') ?? 'N/A';
            });
        
        CRUD::column('description')
            ->label(trans('admin.page_categories.description'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();
                $description = $entry->getDescription($currentLocale) ?? $entry->getDescription('cs') ?? '';
                return \Str::limit($description, 100);
            });
        
        CRUD::column('pages_count')
            ->label(trans('admin.page_categories.pages_count'))
            ->type('closure')
            ->function(function ($entry) {
                return $entry->pages_count;
            });
        
        CRUD::column('created_at')
            ->label(trans('admin.common.created_at'));
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(PageCategoryRequest::class);
        
        CRUD::addField([
            'name' => 'name',
            'label' => trans('admin.page_categories.name'),
            'type' => 'multilingual_text',
            'hint' => trans('admin.validation.page_categories.at_least_one_locale_required'),
        ]);

        CRUD::addField([
            'name' => 'slug',
            'label' => trans('admin.page_categories.slug'),
            'type' => 'multilingual_text',
            'hint' => trans('admin.page_categories.leave_empty_for_autogeneration'),
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => trans('admin.page_categories.description'),
            'type' => 'multilingual_textarea',
            'rows' => 4,
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Define what happens when the Show operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-show
     * @return void
     */
    protected function setupShowOperation()
    {
        // Use same columns as list operation but with more detail
        $this->setupListOperation();
        
        // Add more detailed columns for show view
        CRUD::column('created_at')
            ->label(trans('admin.common.created_at'))
            ->type('datetime');
            
        CRUD::column('updated_at')
            ->label(trans('admin.common.updated_at'))
            ->type('datetime');
    }

}
