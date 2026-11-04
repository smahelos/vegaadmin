<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ArchivePolicyRequest;
use App\Models\ArchivePolicy;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Archive Policy CRUD Controller
 */
class ArchivePolicyCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object
     */
    public function setup(): void
    {
        CRUD::setModel(ArchivePolicy::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/archive-policy',
        );
        CRUD::setEntityNameStrings(
            __('admin/database.archive_policies.entity.singular'),
            __('admin/database.archive_policies.entity.plural'),
        );
    }

    /**
     * Define what happens when the List operation is loaded
     */
    protected function setupListOperation(): void
    {
        $column = CRUD::column('table_name');
        $column->label(
            __('admin/database.archive_policies.fields.table_name'),
        );
        $column->type('text');

        $column = CRUD::column('retention_months');
        $column->label(
            __('admin/database.archive_policies.fields.retention_months'),
        );
        $column->type('number');
        $column->suffix(
            __(
                'admin/database.archive_policies.hints.retention_months_suffix',
            ),
        );

        $column = CRUD::column('date_column');
        $column->label(
            __('admin/database.archive_policies.fields.date_column'),
        );
        $column->type('text');

        $column = CRUD::column('is_active');
        $column->label(
            __('admin/database.archive_policies.fields.is_active'),
        );
        $column->type('boolean');

        $column = CRUD::column('created_at');
        $column->label(
            __('admin/database.archive_policies.fields.created_at'),
        );
        $column->type('datetime');
    }

    /**
     * Define what happens when the Create operation is loaded
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ArchivePolicyRequest::class);

        // Add detailed description above form fields
        CRUD::addField(
            [
                'name' => 'archive_policy_form_description',
                'type' => 'custom_html',
                'value' => '<div class="alert alert-info mb-4">
                            <h6 class="alert-heading"><i class="la la-info-circle"></i> ' . __('admin/database.archive_policies.form_help.title') . '</h6>
                            <p class="mb-2">' . __('admin/database.archive_policies.form_help.description') . '</p>
                            <hr>
                            <ul class="mb-0">
                                <li><strong>' . __('admin/database.archive_policies.fields.table_name') . ':</strong> ' . __('admin/database.archive_policies.form_help.table_name') . '</li>
                                <li><strong>' . __('admin/database.archive_policies.fields.retention_months') . ':</strong> ' . __('admin/database.archive_policies.form_help.retention_months') . '</li>
                                <li><strong>' . __('admin/database.archive_policies.fields.date_column') . ':</strong> ' . __('admin/database.archive_policies.form_help.date_column') . '</li>
                                <li><strong>' . __('admin/database.archive_policies.fields.is_active') . ':</strong> ' . __('admin/database.archive_policies.form_help.is_active') . '</li>
                                <li><strong>' . __('admin/database.archive_policies.fields.description') . ':</strong> ' . __('admin/database.archive_policies.form_help.description') . '</li>
                            </ul>
                        </div>',
            ],
        );

        $field = CRUD::field('table_name');
        $field->label(
            __('admin/database.archive_policies.fields.table_name'),
        );
        $field->type('text');
        $field->hint(
            __('admin/database.archive_policies.hints.table_name'),
        );

        $field = CRUD::field('retention_months');
        $field->label(
            __('admin/database.archive_policies.fields.retention_months'),
        );
        $field->type('number');
        $field->attributes(['min' => 1, 'max' => 120]);
        $field->hint(
            __('admin/database.archive_policies.hints.retention_months'),
        );

        $field = CRUD::field('date_column');
        $field->label(
            __('admin/database.archive_policies.fields.date_column'),
        );
        $field->type('text');
        $field->hint(
            __('admin/database.archive_policies.hints.date_column'),
        );

        $field = CRUD::field('is_active');
        $field->label(
            __('admin/database.archive_policies.fields.is_active'),
        );
        $field->type('boolean');
        $field->default(true);

        $field = CRUD::field('description');
        $field->label(
            __('admin/database.archive_policies.fields.description'),
        );
        $field->type('textarea');
        $field->hint(
            __('admin/database.archive_policies.hints.description'),
        );
    }

    /**
     * Define what happens when the Update operation is loaded
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    /**
     * Define what happens when the Show operation is loaded
     */
    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        $column = CRUD::column('description');
        $column->label(
            __('admin/database.archive_policies.fields.description'),
        );
        $column->type('textarea');
    }
}
