<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PageRequest;
use App\Models\PageCategory;
use App\Models\Page;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PageCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PageCrudController extends CrudController
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
        CRUD::setModel(Page::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/page');
        CRUD::setEntityNameStrings(
            trans('admin.pages.page'),
            trans('admin.pages.pages')
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
            ->label(trans('admin.pages.name'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();
                return $entry->getName($currentLocale) ?? $entry->getName('cs') ?? 'N/A';
            });
        
        CRUD::column('slug')
            ->label(trans('admin.pages.slug'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();
                return $entry->getSlug($currentLocale) ?? $entry->getSlug('cs') ?? 'N/A';
            });
        
        CRUD::column('category_id')
            ->label(trans('admin.pages.category'))
            ->type('closure')
            ->function(function ($entry) {
                if ($entry->category) {
                    $currentLocale = app()->getLocale();
                    return $entry->category->getName($currentLocale) ?? $entry->category->getName('cs') ?? 'N/A';
                }
                return 'N/A';
            });
        
        CRUD::column('parent_id')
            ->label(trans('admin.pages.parent'))
            ->type('closure')
            ->function(function ($entry) {
                if ($entry->parent) {
                    $currentLocale = app()->getLocale();
                    return $entry->parent->getName($currentLocale) ?? $entry->parent->getName('cs') ?? 'N/A';
                }
                return '-';
            });
        
        CRUD::column('sort_order')
            ->label(trans('admin.pages.sort_order'))
            ->type('number');
        
        CRUD::column('published')
            ->label(trans('admin.pages.published'))
            ->type('boolean');
        
        CRUD::column('publishing_start')
            ->label(trans('admin.pages.publishing_start'))
            ->type('datetime');
        
        CRUD::column('created_at')
            ->label(trans('admin.general.created_at'));
        
        // Add filters
        $this->crud->addFilter([
            'name'  => 'published',
            'type'  => 'simple',
            'label' => trans('admin.pages.published')
        ], [
            1 => trans('admin.pages.yes'),
            0 => trans('admin.pages.no'),
        ], function ($value) {
            $this->crud->addClause('where', 'published', $value);
        });
        
        $this->crud->addFilter([
            'name'  => 'category_id',
            'type'  => 'select2',
            'label' => trans('admin.pages.category')
        ], PageCategory::all()->mapWithKeys(function ($category) {
            $currentLocale = app()->getLocale();
            $name = $category->getName($currentLocale) ?? $category->getName('cs') ?? 'N/A';
            return [$category->id => $name];
        })->toArray(), function ($value) {
            $this->crud->addClause('where', 'category_id', $value);
        });
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(PageRequest::class);
        
        // Multilingual basic fields
        CRUD::addField([
            'name' => 'name',
            'label' => trans('admin.pages.name'),
            'type' => 'multilingual_text',
            'hint' => trans('admin.validation.pages.at_least_one_locale_required'),
        ]);
        
        CRUD::addField([
            'name' => 'slug',
            'label' => trans('admin.pages.slug'),
            'type' => 'multilingual_text',
            'hint' => trans('admin.pages.leave_empty_for_autogeneration'),
        ]);
        
        // Category and parent
        $currentLocale = app()->getLocale();
        
        $categoryOptions = PageCategory::all()->mapWithKeys(function ($category) use ($currentLocale) {
            $name = $category->getName($currentLocale) ?? $category->getName('cs') ?? 'N/A';
            return [$category->id => $name];
        })->toArray();
        
        CRUD::field('category_id')
            ->label(trans('admin.pages.category'))
            ->type('select_from_array')
            ->options($categoryOptions)
            ->wrapper(['class' => 'form-group col-md-6']);
        
        $pageOptions = ['' => '- Bez nadřazené stránky -'];
        $pages = Page::all();
        foreach ($pages as $page) {
            $name = $page->getName($currentLocale) ?? $page->getName('cs') ?? 'N/A';
            $pageOptions[$page->id] = $name;
        }
        
        CRUD::field('parent_id')
            ->label(trans('admin.pages.parent'))
            ->type('select_from_array')
            ->options($pageOptions)
            ->wrapper(['class' => 'form-group col-md-6']);
        
        CRUD::field('sort_order')
            ->label(trans('admin.pages.sort_order'))
            ->type('number')
            ->hint(trans('admin.pages.sort_order_help'))
            ->wrapper(['class' => 'form-group col-md-6']);
        
        // Multilingual content fields
        CRUD::addField([
            'name' => 'description',
            'label' => trans('admin.pages.description'),
            'type' => 'multilingual_textarea',
            'rows' => 4,
        ]);
        
        CRUD::addField([
            'name' => 'content',
            'label' => trans('admin.pages.content'),
            'type' => 'multilingual_ckeditor',
        ]);
        
        // Multilingual meta fields
        CRUD::addField([
            'name' => 'meta_title',
            'label' => trans('admin.pages.meta_title'),
            'type' => 'multilingual_text',
        ]);
        
        CRUD::addField([
            'name' => 'meta_description',
            'label' => trans('admin.pages.meta_description'),
            'type' => 'multilingual_textarea',
            'rows' => 3,
        ]);
        
        CRUD::addField([
            'name' => 'meta_keywords',
            'label' => trans('admin.pages.meta_keywords'),
            'type' => 'multilingual_textarea',
            'rows' => 2,
        ]);
        
        // Images
        CRUD::field('main_image')
            ->type('image_with_preview')
            ->label(trans('admin.pages.main_image'))
            ->upload(true)
            ->hint(trans('admin.pages.main_image_help'))
            ->wrapper(['class' => 'form-group col-md-6']);
        
        // Publishing fields
        CRUD::field('published')
            ->label(trans('admin.pages.published'))
            ->type('checkbox')
            ->wrapper(['class' => 'form-group col-md-3']);
        
        CRUD::field('publishing_start')
            ->label(trans('admin.pages.publishing_start'))
            ->type('datetime_picker')
            ->wrapper(['class' => 'form-group col-md-4']);
        
        CRUD::field('publishing_end')
            ->label(trans('admin.pages.publishing_end'))
            ->type('datetime_picker')
            ->wrapper(['class' => 'form-group col-md-4']);
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
        CRUD::column('description')
            ->label(trans('admin.pages.description'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();
                return $entry->getDescription($currentLocale) ?? $entry->getDescription('cs') ?? '';
            });
            
        CRUD::column('content')
            ->label(trans('admin.pages.content'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();
                $content = $entry->getContent($currentLocale) ?? $entry->getContent('cs') ?? '';
                return \Str::limit(strip_tags($content), 200);
            });
        
        CRUD::column('meta_title')
            ->label(trans('admin.pages.meta_title'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();
                return $entry->getMetaTitle($currentLocale) ?? $entry->getMetaTitle('cs') ?? '';
            });
        
        CRUD::column('created_at')
            ->label(trans('admin.general.created_at'))
            ->type('datetime');
            
        CRUD::column('updated_at')
            ->label(trans('admin.general.updated_at'))
            ->type('datetime');
    }

    /**
     * Transform multilingual field data before storing.
     */
    protected function transformMultilingualData($request)
    {
        $locales = ['cs', 'en', 'de', 'sk'];
        $multilingualFields = ['name', 'slug', 'description', 'content', 'meta_title', 'meta_description', 'meta_keywords'];
        
        $data = $request->all();
        
        foreach ($multilingualFields as $field) {
            $fieldData = [];
            
            foreach ($locales as $locale) {
                $key = $field . '_' . $locale;
                if (isset($data[$key]) && !empty($data[$key])) {
                    $fieldData[$locale] = $data[$key];
                }
                // Remove the original key from data
                unset($data[$key]);
            }
            
            // Add the transformed data if we have any data for this field
            if (!empty($fieldData)) {
                $data[$field] = $fieldData;
            }
        }
        
        return $data;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        
        // Execute the FormRequest authorization and validation logic
        $request = $this->crud->validateRequest();
        
        // Transform multilingual data
        $data = $this->transformMultilingualData($request);
        
        // Insert item in the db
        $item = $this->crud->create($data);
        $this->data['entry'] = $this->crud->entry = $item;

        // Show a success message
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        
        // Execute the FormRequest authorization and validation logic
        $request = $this->crud->validateRequest();
        
        // Transform multilingual data
        $data = $this->transformMultilingualData($request);
        
        // Update the row in the db
        $item = $this->crud->update(
            $request->get($this->crud->model->getKeyName()), 
            $data
        );
        $this->data['entry'] = $this->crud->entry = $item;

        // Show a success message
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }
}
