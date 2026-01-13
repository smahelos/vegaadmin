<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Requests\Admin\SubscriptionPlanRequest;
use Prologue\Alerts\Facades\Alert;

/**
 * SubscriptionPlan CRUD controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SubscriptionPlanCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    // Access control is configured via CrudAccessServiceInterface in setup()

    /**
     * Configure the CrudPanel object and check user permissions
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\SubscriptionPlan::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/subscription-plan');
        CRUD::setEntityNameStrings(
            trans('admin.subscription_plans.entity_singular'),
            trans('admin.subscription_plans.entity_plural')
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
                'label' => trans('admin.subscription_plans.name'),
                'type' => 'text',
            ],
            [
                'name' => 'price',
                'label' => trans('admin.subscription_plans.price'),
                'type' => 'number',
                'decimals' => 2,
                'suffix' => ' CZK',
            ],
            [
                'name' => 'currency',
                'label' => trans('admin.subscription_plans.currency'),
                'type' => 'text',
            ],
            [
                'name' => 'billing_period',
                'label' => trans('admin.subscription_plans.billing_period'),
                'type' => 'select_from_array',
                'options' => [
                    'monthly' => trans('admin.subscription_plans.monthly'),
                    'yearly' => trans('admin.subscription_plans.yearly'),
                ],
            ],
            [
                'name' => 'billing_interval',
                'label' => trans('admin.subscription_plans.billing_interval'),
                'type' => 'number',
            ],
            [
                'name' => 'trial_days',
                'label' => trans('admin.subscription_plans.trial_days'),
                'type' => 'number',
            ],
            [
                'name' => 'is_active',
                'label' => trans('admin.subscription_plans.is_active'),
                'type' => 'boolean',
                'options' => [
                    0 => trans('admin.general.no'),
                    1 => trans('admin.general.yes'),
                ],
            ],
        ]);

        // Add filters
        if (backpack_pro()) {
            CRUD::addFilter([
                'type' => 'simple',
                'name' => 'is_active',
                'label' => trans('admin.subscription_plans.active_only'),
            ],
            false,
            function () {
                CRUD::addClause('where', 'is_active', '1');
            });

            CRUD::addFilter([
                'name' => 'billing_period',
                'type' => 'select2',
                'label' => trans('admin.subscription_plans.billing_period'),
            ],
            [
                'monthly' => trans('admin.subscription_plans.monthly'),
                'yearly' => trans('admin.subscription_plans.yearly'),
            ],
            function ($value) {
                CRUD::addClause('where', 'billing_period', $value);
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
        CRUD::setValidation(SubscriptionPlanRequest::class);

        CRUD::addFields([
            [
                'name' => 'name',
                'label' => trans('admin.subscription_plans.name'),
                'type' => 'text',
                'attributes' => [
                    'placeholder' => trans('admin.subscription_plans.name_placeholder'),
                ],
            ],
            [
                'name' => 'description',
                'label' => trans('admin.subscription_plans.description'),
                'type' => 'textarea',
                'attributes' => [
                    'placeholder' => trans('admin.subscription_plans.description_placeholder'),
                ],
            ],
            [
                'name' => 'price',
                'label' => trans('admin.subscription_plans.price'),
                'type' => 'number',
                'attributes' => [
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => trans('admin.subscription_plans.price_placeholder'),
                ],
            ],
            [
                'name' => 'currency',
                'label' => trans('admin.subscription_plans.currency'),
                'type' => 'select_from_array',
                'options' => [
                    'CZK' => 'CZK',
                    'EUR' => 'EUR',
                    'USD' => 'USD',
                ],
                'default' => 'CZK',
            ],
            [
                'name' => 'billing_period',
                'label' => trans('admin.subscription_plans.billing_period'),
                'type' => 'select_from_array',
                'options' => [
                    'monthly' => trans('admin.subscription_plans.monthly'),
                    'yearly' => trans('admin.subscription_plans.yearly'),
                ],
                'default' => 'monthly',
            ],
            [
                'name' => 'billing_interval',
                'label' => trans('admin.subscription_plans.billing_interval'),
                'type' => 'number',
                'attributes' => [
                    'min' => '1',
                    'placeholder' => trans('admin.subscription_plans.billing_interval_placeholder'),
                ],
                'default' => 1,
            ],
            [
                'name' => 'trial_days',
                'label' => trans('admin.subscription_plans.trial_days'),
                'type' => 'number',
                'attributes' => [
                    'min' => '0',
                    'placeholder' => trans('admin.subscription_plans.trial_days_placeholder'),
                ],
                'default' => 0,
            ],
            [
                'name' => 'features',
                'entity' => 'features',
                'label' => trans('admin.subscription_plans.features'),
                'type' => 'checklist',
                'hint' => trans('admin.subscription_plans.features_hint'),
                'model' => \App\Models\SubscriptionPlanFeature::class,
                'attribute' => 'name',
                'pivot' => true,
                'show_select_all' => true,
                'number_of_columns' => 2,
            ],
            [
                'name' => 'is_active',
                'label' => trans('admin.subscription_plans.is_active'),
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
            'name' => 'description',
            'label' => trans('admin.subscription_plans.description'),
            'type' => 'textarea',
        ]);
        
        CRUD::addColumn([
            'name' => 'features',
            'label' => trans('admin.subscription_plans.features'),
            'type' => 'relationship',
            'entity' => 'features',
            'attribute' => 'name',
            'limit' => 50,
        ]);
        
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

    /**
     * Store a new subscription plan
     */
    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        // Execute the FormRequest authorization and validation logic
        $request = $this->crud->validateRequest();

        // Extract features before saving
        $features = $request->input('features', []);
        $requestData = $request->except('features');

        // Create the subscription plan
        $item = $this->crud->create($requestData);

        // Sync the features
        if (!empty($features)) {
            $item->features()->sync($features);
        }

        Alert::success(trans('backpack::crud.insert_success'))->flash();

        // Save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Update an existing subscription plan
     */
    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        // Execute the FormRequest authorization and validation logic
        $request = $this->crud->validateRequest();

        // Extract features before saving
        $features = $request->input('features', []);
        $requestData = $request->except('features');

        // Update the subscription plan
        $item = $this->crud->update(
            $request->get($this->crud->model->getKeyName()),
            $requestData
        );

        // Sync the features
        $item->features()->sync($features);

        Alert::success(trans('backpack::crud.update_success'))->flash();

        // Save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }
}
