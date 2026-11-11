<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
use App\Http\Controllers\Admin\Operations\RestrictedDeleteOperation;
use App\Http\Requests\Admin\StatusRequest;
use App\Models\Status;
use App\Models\StatusCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Status management controller
 *
 * @property-read CrudPanel $crud
 */
final class StatusCrudController extends CrudController
{
    use CreateOperation;
    use GatesCrudOperations;
    use ListOperation;
    use RestrictedDeleteOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object
     */
    public function setup(): void
    {
        CRUD::setModel(Status::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/status',
        );
        CRUD::setEntityNameStrings(
            __('statuses.entity.singular'),
            __('statuses.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => Permission::STATUS_VIEW,
                'show' => Permission::STATUS_VIEW,
                'create' => Permission::STATUS_CREATE,
                'update' => Permission::STATUS_UPDATE,
                'delete' => Permission::STATUS_DELETE,
            ],
        );

        // Filter by category_id from URL
        $request = request();
        if ($request->has('category_id')) {
            $this->crud->addClause(
                'where',
                'category_id',
                $request->input('category_id'),
            );

            // Get category name for a more descriptive page title
            $category = StatusCategory::find(
                $request->input('category_id'),
            );
            if ($category) {
                CRUD::setEntityNameStrings(
                    $category->name . ' ' . __('statuses.entity.singular'),
                    $category->name . ' ' . __('statuses.entity.plural'),
                );
            }
        }
    }

    /**
     * Setup list view columns
     */
    protected function setupListOperation(): void
    {
        $column = CRUD::column('name');
        $column->label(
            __('statuses.fields.name'),
        );
        $column = CRUD::column('slug');
        $column->label(
            __('statuses.fields.slug'),
        );

        // Add column for category
        CRUD::addColumn(
            [
                'name' => 'category',
                'label' => __('statuses.fields.category_id'),
                'type' => 'relationship',
                'relation_type' => 'BelongsTo',
                'entity' => 'category',
                'model' => StatusCategory::class,
                'attribute' => 'name',
                'wrapper' => [
                    'href' => function (
                        $crud,
                        $column,
                        $entry,
                        $related_key,
                    ) {
                        return backpack_url('status-category/' . $entry->category_id . '/show');
                    },
                ],
            ],
        );

        // Add a filter for category
        CRUD::addFilter(
            [
                'name' => 'category_id',
                'type' => 'select2',
                'label' => __('statuses.filters.category'),
            ],
            function () {
                $categoryNames = StatusCategory::pluck('name', 'id');

                return $categoryNames->toArray();
            },
            function ($value): void {
                CRUD::addClause(
                    'where',
                    'category_id',
                    $value,
                );
            },
        );

        $column = CRUD::column('color_preview');
        $column->type('custom_html');
        $column->label(
            __('statuses.fields.color'),
        );
        $column->value(fn ($entry) => $entry->color_preview);

        CRUD::addColumn(
            [
                'name' => 'is_active',
                'label' => __('statuses.fields.is_active'),
                'type' => 'boolean',
            ],
        );
    }

    /**
     * Setup create form fields
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(StatusRequest::class);

        $field = CRUD::field('name');
        $field->label(
            __('statuses.fields.name'),
        );
        $field = CRUD::field('slug');
        $field->label(
            __('statuses.fields.slug'),
        );

        // Add field for category selection
        CRUD::addField(
            [
                'name' => 'category_id',
                'label' => __('statuses.fields.category_id'),
                'type' => 'relationship',
                'entity' => 'category',
                'attribute' => 'name',
                'required' => true,
                'model' => StatusCategory::class,
            ],
        );

        CRUD::addField(
            [
                'name' => 'color',
                'label' => __('statuses.fields.color'),
                'type' => 'select_from_array',
                'options' => [
                    'green' => __('general.colors.green'),
                    'yellow' => __('general.colors.yellow'),
                    'red' => __('general.colors.red'),
                    'blue' => __('general.colors.blue'),
                    'gray' => __('general.colors.gray'),
                    'purple' => __('general.colors.purple'),
                    'indigo' => __('general.colors.indigo'),
                    'pink' => __('general.colors.pink'),
                ],
                'allows_null' => false,
                'default' => 'gray',
            ],
        );
        $field = CRUD::field('is_active');
        $field->label(
            __('statuses.fields.is_active'),
        );
        $field->type('boolean');
    }

    /**
     * Setup update form fields
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }
}
