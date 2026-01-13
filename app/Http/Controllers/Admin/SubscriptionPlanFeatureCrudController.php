<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SubscriptionPlanFeatureRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * SubscriptionPlanFeature CRUD controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SubscriptionPlanFeatureCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    // Access control is configured via application CrudAccessServiceInterface in setup()

    /**
     * Configure the CrudPanel object and check user permissions
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\SubscriptionPlanFeature::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/subscription-plan-feature');
        CRUD::setEntityNameStrings(
            trans('admin.subscription_plan_features.entity_singular'),
            trans('admin.subscription_plan_features.entity_plural')
        );

        // Configure access using centralized service
        $userId = optional(backpack_user())->id ?? 0;
        app(\App\Application\User\Contracts\CrudAccessServiceInterface::class)
            ->configureCrudAccess($this->crud, (int) $userId);
    }

    /**
     * Setup list view columns
     * 
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumns([
            [
                'name' => 'name',
                'label' => trans('admin.subscription_plan_features.name'),
                'type' => 'text',
            ],
            [
                'name' => 'slug',
                'label' => trans('admin.subscription_plan_features.slug'),
                'type' => 'text',
            ],
            [
                'name' => 'description',
                'label' => trans('admin.subscription_plan_features.description'),
                'type' => 'text',
                'limit' => 100,
            ],
            [
                'name' => 'sort_order',
                'label' => trans('admin.subscription_plan_features.sort_order'),
                'type' => 'number',
            ],
            [
                'name' => 'is_active',
                'label' => trans('admin.subscription_plan_features.is_active'),
                'type' => 'boolean',
                'options' => [
                    0 => trans('admin.general.no'),
                    1 => trans('admin.general.yes'),
                ],
            ],
        ]);

        // Set default order
        CRUD::orderBy('sort_order', 'ASC');
        CRUD::orderBy('name', 'ASC');

        // Add filters
        if (backpack_pro()) {
            CRUD::addFilter([
                'type' => 'simple',
                'name' => 'is_active',
                'label' => trans('admin.subscription_plan_features.active_only'),
            ],
            false,
            function () {
                CRUD::addClause('where', 'is_active', '1');
            });
        }
    }

    /**
     * Setup create and update forms
     * 
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SubscriptionPlanFeatureRequest::class);

        CRUD::addFields([
            [
                'name' => 'name',
                'label' => trans('admin.subscription_plan_features.name'),
                'type' => 'text',
                'attributes' => [
                    'placeholder' => trans('admin.subscription_plan_features.name_placeholder'),
                ],
            ],
            [
                'name' => 'slug',
                'label' => trans('admin.subscription_plan_features.slug'),
                'type' => 'text',
                'attributes' => [
                    'placeholder' => trans('admin.subscription_plan_features.slug_placeholder'),
                ],
                'hint' => trans('admin.subscription_plan_features.slug_placeholder'),
            ],
            [
                'name' => 'description',
                'label' => trans('admin.subscription_plan_features.description'),
                'type' => 'textarea',
                'attributes' => [
                    'placeholder' => trans('admin.subscription_plan_features.description_placeholder'),
                ],
            ],
            [
                'name' => 'sort_order',
                'label' => trans('admin.subscription_plan_features.sort_order'),
                'type' => 'number',
                'attributes' => [
                    'min' => '0',
                    'placeholder' => trans('admin.subscription_plan_features.sort_order_placeholder'),
                ],
                'hint' => trans('admin.subscription_plan_features.sort_order_hint'),
                'default' => 0,
            ],
            [
                'name' => 'is_active',
                'label' => trans('admin.subscription_plan_features.is_active'),
                'type' => 'checkbox',
                'default' => true,
            ],
        ]);
    }

    /**
     * Setup update operation
     * 
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Setup show operation
     * 
     * @return void
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => trans('admin.general.created_at'),
            'type' => 'datetime',
        ]);
        
        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => trans('admin.general.updated_at'),
            'type' => 'datetime',
        ]);
    }
}
