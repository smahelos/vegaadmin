<?php

namespace App\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Class PageCrudController
 *
 * @property-read CrudPanel $crud
 */
class PageCrudController extends CrudController
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
        CRUD::setModel(Page::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/page',
        );
        CRUD::setEntityNameStrings(
            __('pages.entity.singular'),
            __('pages.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => Permission::PAGE_VIEW,
                'show' => Permission::PAGE_VIEW,
                'create' => Permission::PAGE_CREATE,
                'update' => Permission::PAGE_UPDATE,
                'delete' => Permission::PAGE_DELETE,
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
            __('pages.fields.name'),
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
            __('pages.fields.slug'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                $application = app();
                $currentLocale = $application->getLocale();

                return $entry->getSlug($currentLocale) ?? $entry->getSlug('cs') ?? __('general.placeholders.not_available');
            },
        );

        $column = CRUD::column('category_id');
        $column->label(
            __('pages.fields.category_id'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                if ($entry->category) {
                    $application = app();
                    $currentLocale = $application->getLocale();

                    return $entry->category->getName($currentLocale) ?? $entry->category->getName('cs') ?? __('general.placeholders.not_available');
                }

                return __('general.placeholders.not_available');
            },
        );

        $column = CRUD::column('parent_id');
        $column->label(
            __('pages.fields.parent_id'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                if ($entry->parent) {
                    $application = app();
                    $currentLocale = $application->getLocale();

                    return $entry->parent->getName($currentLocale) ?? $entry->parent->getName('cs') ?? __('general.placeholders.not_available');
                }

                return '-';
            },
        );

        $column = CRUD::column('sort_order');
        $column->label(
            __('pages.fields.sort_order'),
        );
        $column->type('number');

        $column = CRUD::column('published');
        $column->label(
            __('pages.fields.published'),
        );
        $column->type('boolean');

        $column = CRUD::column('publishing_start');
        $column->label(
            __('pages.fields.publishing_start'),
        );
        $column->type('datetime');

        $column = CRUD::column('created_at');
        $column->label(
            __('pages.fields.created_at'),
        );

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

        $pageCategories = PageCategory::all();
        $categoryNames = $pageCategories->mapWithKeys(
            function ($category) {
                $application = app();
                $currentLocale = $application->getLocale();
                $name = $category->getName($currentLocale) ?? $category->getName('cs') ?? __('general.placeholders.not_available');

                return [$category->id => $name];
            },
        );

        $this->crud->addFilter(
            [
                'name' => 'category_id',
                'type' => 'select2',
                'label' => __('pages.fields.category_id'),
            ],
            $categoryNames->toArray(),
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
        CRUD::addField(
            [
                'name' => 'name',
                'label' => __('pages.fields.name'),
                'type' => 'multilingual',
                'hint' => __('pages.hints.name'),
            ],
        );

        CRUD::addField(
            [
                'name' => 'slug',
                'label' => __('pages.fields.slug'),
                'type' => 'multilingual',
                'hint' => __('pages.hints.slug'),
            ],
        );

        // Category and parent
        $application = app();
        $currentLocale = $application->getLocale();

        $pageCategories = PageCategory::all();
        $categoryNames = $pageCategories->mapWithKeys(
            function ($category) use ($currentLocale) {
                $name = $category->getName($currentLocale) ?? $category->getName('cs') ?? __('general.placeholders.not_available');

                return [$category->id => $name];
            },
        );
        $categoryOptions = $categoryNames->toArray();

        $field = CRUD::field('category_id');
        $field->label(
            __('pages.fields.category_id'),
        );
        $field->type('select_from_array');
        $field->options($categoryOptions);
        $field->wrapper(['class' => 'form-group col-md-6']);

        $pageOptions = ['' => __('pages.placeholders.no_parent')];
        $pages = Page::all();
        foreach ($pages as $page) {
            $name = $page->getName($currentLocale) ?? $page->getName('cs') ?? __('general.placeholders.not_available');
            $pageOptions[$page->id] = $name;
        }

        $field = CRUD::field('parent_id');
        $field->label(
            __('pages.fields.parent_id'),
        );
        $field->type('select_from_array');
        $field->options($pageOptions);
        $field->wrapper(['class' => 'form-group col-md-6']);

        $field = CRUD::field('sort_order');
        $field->label(
            __('pages.fields.sort_order'),
        );
        $field->type('number');
        $field->hint(
            __('pages.hints.sort_order'),
        );
        $field->wrapper(['class' => 'form-group col-md-6']);

        // Multilingual content fields
        CRUD::addField(
            [
                'name' => 'description',
                'label' => __('pages.fields.description'),
                'type' => 'multilingual',
                'field_type' => 'textarea',
                'attributes' => ['rows' => 4],
            ],
        );

        CRUD::addField(
            [
                'name' => 'content',
                'label' => __('pages.fields.content'),
                'type' => 'multilingual',
                'field_type' => 'summernote',
                'options' => ['height' => 300],
            ],
        );

        // Multilingual meta fields
        CRUD::addField(
            [
                'name' => 'meta_title',
                'label' => __('pages.fields.meta_title'),
                'type' => 'multilingual',
            ],
        );

        CRUD::addField(
            [
                'name' => 'meta_description',
                'label' => __('pages.fields.meta_description'),
                'type' => 'multilingual',
                'field_type' => 'textarea',
                'attributes' => ['rows' => 3],
            ],
        );

        CRUD::addField(
            [
                'name' => 'meta_keywords',
                'label' => __('pages.fields.meta_keywords'),
                'type' => 'multilingual',
                'field_type' => 'textarea',
                'attributes' => ['rows' => 2],
            ],
        );

        // Images
        $field = CRUD::field('main_image');
        $field->type('image_with_preview');
        $field->label(
            __('pages.fields.main_image'),
        );
        $field->upload(true);
        $field->hint(
            __('pages.hints.main_image'),
        );
        $field->wrapper(['class' => 'form-group col-md-6']);

        // Publishing fields
        $field = CRUD::field('published');
        $field->label(
            __('pages.fields.published'),
        );
        $field->type('checkbox');
        $field->wrapper(['class' => 'form-group col-md-3']);

        $field = CRUD::field('publishing_start');
        $field->label(
            __('pages.fields.publishing_start'),
        );
        $field->type('datepicker');
        $field->datepicker_options(['timepicker' => true]);
        $field->wrapper(['class' => 'form-group col-md-4']);

        $field = CRUD::field('publishing_end');
        $field->label(
            __('pages.fields.publishing_end'),
        );
        $field->type('datepicker');
        $field->datepicker_options(['timepicker' => true]);
        $field->wrapper(['class' => 'form-group col-md-4']);
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
        $column = CRUD::column('description');
        $column->label(
            __('pages.fields.description'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                $application = app();
                $currentLocale = $application->getLocale();

                return $entry->getDescription($currentLocale) ?? $entry->getDescription('cs') ?? '';
            },
        );

        $column = CRUD::column('content');
        $column->label(
            __('pages.fields.content'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                $application = app();
                $currentLocale = $application->getLocale();
                $content = $entry->getContent($currentLocale) ?? $entry->getContent('cs') ?? '';

                return \Str::limit(
                    strip_tags($content),
                    200,
                );
            },
        );

        $column = CRUD::column('meta_title');
        $column->label(
            __('pages.fields.meta_title'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                $application = app();
                $currentLocale = $application->getLocale();

                return $entry->getMetaTitle($currentLocale) ?? $entry->getMetaTitle('cs') ?? '';
            },
        );

        $column = CRUD::column('created_at');
        $column->label(
            __('pages.fields.created_at'),
        );
        $column->type('datetime');

        $column = CRUD::column('updated_at');
        $column->label(
            __('pages.fields.updated_at'),
        );
        $column->type('datetime');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse|RedirectResponse
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
    public function update(): JsonResponse|RedirectResponse
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
