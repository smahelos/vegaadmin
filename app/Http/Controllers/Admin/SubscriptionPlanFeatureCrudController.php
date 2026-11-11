<?php

namespace App\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
use App\Http\Requests\Admin\SubscriptionPlanFeatureRequest;
use App\Models\SubscriptionPlanFeature;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * SubscriptionPlanFeature CRUD controller
 *
 * @property-read CrudPanel $crud
 */
class SubscriptionPlanFeatureCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object and check user permissions
     */
    public function setup(): void
    {
        CRUD::setModel(SubscriptionPlanFeature::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/subscription-plan-feature',
        );
        CRUD::setEntityNameStrings(
            __('subscriptions.features.entity.singular'),
            __('subscriptions.features.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => Permission::SUBSCRIPTION_PLAN_FEATURE_VIEW,
                'show' => Permission::SUBSCRIPTION_PLAN_FEATURE_VIEW,
                'create' => Permission::SUBSCRIPTION_PLAN_FEATURE_CREATE,
                'update' => Permission::SUBSCRIPTION_PLAN_FEATURE_UPDATE,
                'delete' => Permission::SUBSCRIPTION_PLAN_FEATURE_DELETE,
            ],
        );
    }

    /**
     * Setup list view columns
     */
    protected function setupListOperation(): void
    {
        CRUD::addColumns(
            [
                [
                    'name' => 'name',
                    'label' => __('subscriptions.features.fields.name'),
                    'type' => 'text',
                ],
                [
                    'name' => 'slug',
                    'label' => __('subscriptions.features.fields.slug'),
                    'type' => 'text',
                ],
                [
                    'name' => 'description',
                    'label' => __('subscriptions.features.fields.description'),
                    'type' => 'text',
                    'limit' => 100,
                ],
                [
                    'name' => 'sort_order',
                    'label' => __('subscriptions.features.fields.sort_order'),
                    'type' => 'number',
                ],
                [
                    'name' => 'is_active',
                    'label' => __('subscriptions.features.fields.is_active'),
                    'type' => 'boolean',
                    'options' => [
                        0 => __('general.no'),
                        1 => __('general.yes'),
                    ],
                ],
            ],
        );

        // Set default order
        CRUD::orderBy('sort_order', 'ASC');
        CRUD::orderBy('name', 'ASC');

        // Add filters
        if (backpack_pro()) {
            CRUD::addFilter(
                [
                    'type' => 'simple',
                    'name' => 'is_active',
                    'label' => __('subscriptions.features.filters.active_only'),
                ],
                false,
                function (): void {
                    CRUD::addClause(
                        'where',
                        'is_active',
                        '1',
                    );
                },
            );
        }
    }

    /**
     * Setup create and update forms
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(SubscriptionPlanFeatureRequest::class);

        CRUD::addFields(
            [
                [
                    'name' => 'name',
                    'label' => __('subscriptions.features.fields.name'),
                    'type' => 'text',
                    'attributes' => [
                        'placeholder' => __('subscriptions.features.placeholders.name'),
                    ],
                ],
                [
                    'name' => 'slug',
                    'label' => __('subscriptions.features.fields.slug'),
                    'type' => 'text',
                    'hint' => __('subscriptions.features.hints.slug'),
                ],
                [
                    'name' => 'description',
                    'label' => __('subscriptions.features.fields.description'),
                    'type' => 'textarea',
                    'attributes' => [
                        'placeholder' => __('subscriptions.features.placeholders.description'),
                    ],
                ],
                [
                    'name' => 'sort_order',
                    'label' => __('subscriptions.features.fields.sort_order'),
                    'type' => 'number',
                    'attributes' => [
                        'min' => '0',
                        'placeholder' => __('subscriptions.features.placeholders.sort_order'),
                    ],
                    'hint' => __('subscriptions.features.hints.sort_order'),
                    'default' => 0,
                ],
                [
                    'name' => 'is_active',
                    'label' => __('subscriptions.features.fields.is_active'),
                    'type' => 'checkbox',
                    'default' => true,
                ],
            ],
        );
    }

    /**
     * Setup update operation
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    /**
     * Setup show operation
     */
    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        CRUD::addColumn(
            [
                'name' => 'created_at',
                'label' => __('subscriptions.features.fields.created_at'),
                'type' => 'datetime',
            ],
        );

        CRUD::addColumn(
            [
                'name' => 'updated_at',
                'label' => __('subscriptions.features.fields.updated_at'),
                'type' => 'datetime',
            ],
        );
    }
}
