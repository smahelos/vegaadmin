<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
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
 * Class PageCrudController
 *
 * @property-read CrudPanel $crud
 */
class PageCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(Page::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/page');
        CRUD::setEntityNameStrings(
            __('pages.entity.singular'),
            __('pages.entity.plural'),
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     */
    protected function setupListOperation(): void
    {
        CRUD::column('name')
            ->label(__('pages.fields.name'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();

                return $entry->getName($currentLocale) ?? $entry->getName('cs') ?? __('general.placeholders.not_available');
            });

        CRUD::column('slug')
            ->label(__('pages.fields.slug'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();

                return $entry->getSlug($currentLocale) ?? $entry->getSlug('cs') ?? __('general.placeholders.not_available');
            });

        CRUD::column('category_id')
            ->label(__('pages.fields.category_id'))
            ->type('closure')
            ->function(function ($entry) {
                if ($entry->category) {
                    $currentLocale = app()->getLocale();

                    return $entry->category->getName($currentLocale) ?? $entry->category->getName('cs') ?? __('general.placeholders.not_available');
                }

                return __('general.placeholders.not_available');
            });

        CRUD::column('parent_id')
            ->label(__('pages.fields.parent_id'))
            ->type('closure')
            ->function(function ($entry) {
                if ($entry->parent) {
                    $currentLocale = app()->getLocale();

                    return $entry->parent->getName($currentLocale) ?? $entry->parent->getName('cs') ?? __('general.placeholders.not_available');
                }

                return '-';
            });

        CRUD::column('sort_order')
            ->label(__('pages.fields.sort_order'))
            ->type('number');

        CRUD::column('published')
            ->label(__('pages.fields.published'))
            ->type('boolean');

        CRUD::column('publishing_start')
            ->label(__('pages.fields.publishing_start'))
            ->type('datetime');

        CRUD::column('created_at')
            ->label(__('pages.fields.created_at'));

        // Add filters
        $this->crud->addFilter(
            [
                'name' => 'published',
                'type' => 'simple',
                'label' => __('pages.fields.published'),
            ],
            [
                1 => __('general.yes'),
                0 => __('general.no'),
            ],
            function ($value): void {
                $this->crud->addClause(
                    'where',
                    'published',
                    $value,
                );
            },
        );

        $this->crud->addFilter(
            [
                'name' => 'category_id',
                'type' => 'select2',
                'label' => __('pages.fields.category_id'),
            ],
            PageCategory::all()->mapWithKeys(function ($category) {
                $currentLocale = app()->getLocale();
                $name = $category->getName($currentLocale) ?? $category->getName('cs') ?? __('general.placeholders.not_available');

                return [$category->id => $name];
            })->toArray(),
            function ($value): void {
                $this->crud->addClause(
                    'where',
                    'category_id',
                    $value,
                );
            },
        );
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(PageRequest::class);

        // Multilingual basic fields
        CRUD::addField([
            'name' => 'name',
            'label' => __('pages.fields.name'),
            'type' => 'multilingual',
            'hint' => __('pages.hints.name'),
        ]);

        CRUD::addField([
            'name' => 'slug',
            'label' => __('pages.fields.slug'),
            'type' => 'multilingual',
            'hint' => __('pages.hints.slug'),
        ]);

        // Category and parent
        $currentLocale = app()->getLocale();

        $categoryOptions = PageCategory::all()->mapWithKeys(function ($category) use ($currentLocale) {
            $name = $category->getName($currentLocale) ?? $category->getName('cs') ?? __('general.placeholders.not_available');

            return [$category->id => $name];
        })->toArray();

        CRUD::field('category_id')
            ->label(__('pages.fields.category_id'))
            ->type('select_from_array')
            ->options($categoryOptions)
            ->wrapper(['class' => 'form-group col-md-6']);

        $pageOptions = ['' => __('pages.placeholders.no_parent')];
        $pages = Page::all();
        foreach ($pages as $page) {
            $name = $page->getName($currentLocale) ?? $page->getName('cs') ?? __('general.placeholders.not_available');
            $pageOptions[$page->id] = $name;
        }

        CRUD::field('parent_id')
            ->label(__('pages.fields.parent_id'))
            ->type('select_from_array')
            ->options($pageOptions)
            ->wrapper(['class' => 'form-group col-md-6']);

        CRUD::field('sort_order')
            ->label(__('pages.fields.sort_order'))
            ->type('number')
            ->hint(__('pages.hints.sort_order'))
            ->wrapper(['class' => 'form-group col-md-6']);

        // Multilingual content fields
        CRUD::addField([
            'name' => 'description',
            'label' => __('pages.fields.description'),
            'type' => 'multilingual',
            'field_type' => 'textarea',
            'attributes' => ['rows' => 4],
        ]);

        CRUD::addField([
            'name' => 'content',
            'label' => __('pages.fields.content'),
            'type' => 'multilingual',
            'field_type' => 'summernote',
            'options' => ['height' => 300],
        ]);

        // Multilingual meta fields
        CRUD::addField([
            'name' => 'meta_title',
            'label' => __('pages.fields.meta_title'),
            'type' => 'multilingual',
        ]);

        CRUD::addField([
            'name' => 'meta_description',
            'label' => __('pages.fields.meta_description'),
            'type' => 'multilingual',
            'field_type' => 'textarea',
            'attributes' => ['rows' => 3],
        ]);

        CRUD::addField([
            'name' => 'meta_keywords',
            'label' => __('pages.fields.meta_keywords'),
            'type' => 'multilingual',
            'field_type' => 'textarea',
            'attributes' => ['rows' => 2],
        ]);

        // Images
        CRUD::field('main_image')
            ->type('image_with_preview')
            ->label(__('pages.fields.main_image'))
            ->upload(true)
            ->hint(__('pages.hints.main_image'))
            ->wrapper(['class' => 'form-group col-md-6']);

        // Publishing fields
        CRUD::field('published')
            ->label(__('pages.fields.published'))
            ->type('checkbox')
            ->wrapper(['class' => 'form-group col-md-3']);

        CRUD::field('publishing_start')
            ->label(__('pages.fields.publishing_start'))
            ->type('datepicker')
            ->datepicker_options(['timepicker' => true])
            ->wrapper(['class' => 'form-group col-md-4']);

        CRUD::field('publishing_end')
            ->label(__('pages.fields.publishing_end'))
            ->type('datepicker')
            ->datepicker_options(['timepicker' => true])
            ->wrapper(['class' => 'form-group col-md-4']);
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
        CRUD::column('description')
            ->label(__('pages.fields.description'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();

                return $entry->getDescription($currentLocale) ?? $entry->getDescription('cs') ?? '';
            });

        CRUD::column('content')
            ->label(__('pages.fields.content'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();
                $content = $entry->getContent($currentLocale) ?? $entry->getContent('cs') ?? '';

                return \Str::limit(strip_tags($content), 200);
            });

        CRUD::column('meta_title')
            ->label(__('pages.fields.meta_title'))
            ->type('closure')
            ->function(function ($entry) {
                $currentLocale = app()->getLocale();

                return $entry->getMetaTitle($currentLocale) ?? $entry->getMetaTitle('cs') ?? '';
            });

        CRUD::column('created_at')
            ->label(__('pages.fields.created_at'))
            ->type('datetime');

        CRUD::column('updated_at')
            ->label(__('pages.fields.updated_at'))
            ->type('datetime');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        // Execute the FormRequest authorization and validation logic
        $request = $this->crud->validateRequest();

        // PageRequest has already merged the per-locale inputs; only validated input reaches the model
        $item = $this->crud->create($request->validated());
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

        // PageRequest has already merged the per-locale inputs; only validated input reaches the model
        $item = $this->crud->update(
            $request->get($this->crud->model->getKeyName()),
            $request->validated(),
        );
        $this->data['entry'] = $this->crud->entry = $item;

        // Show a success message
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }
}
