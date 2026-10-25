<?php

namespace App\Http\Controllers\Admin;

use App\Application\User\Contracts\CrudAccessServiceInterface;
use App\Http\Controllers\Admin\Operations\RestrictedDeleteOperation;
use App\Http\Requests\Admin\SubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\Facades\Alert;

/**
 * SubscriptionPlan CRUD controller
 *
 * @property-read CrudPanel $crud
 */
class SubscriptionPlanCrudController extends CrudController
{
    use CreateOperation;
    use ListOperation;
    use RestrictedDeleteOperation;
    use ShowOperation;
    use UpdateOperation;
    // Access control is configured via CrudAccessServiceInterface in setup()

    /**
     * Configure the CrudPanel object and check user permissions
     */
    public function setup(): void
    {
        CRUD::setModel(SubscriptionPlan::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/subscription-plan');
        CRUD::setEntityNameStrings(
            __('subscriptions.plans.entity.singular'),
            __('subscriptions.plans.entity.plural'),
        );

        // Configure access using centralized service
        $userId = optional(backpack_user())->id ?? 0;
        app(CrudAccessServiceInterface::class)
            ->configureCrudAccess($this->crud, (int) $userId);
    }

    /**
     * Setup list view columns
     */
    protected function setupListOperation(): void
    {
        CRUD::addColumns([
            [
                'name' => 'name',
                'label' => __('subscriptions.plans.fields.name'),
                'type' => 'text',
            ],
            [
                'name' => 'price',
                'label' => __('subscriptions.plans.fields.price'),
                'type' => 'number',
                'decimals' => 2,
                'suffix' => ' CZK',
            ],
            [
                'name' => 'currency',
                'label' => __('subscriptions.plans.fields.currency'),
                'type' => 'text',
            ],
            [
                'name' => 'billing_period',
                'label' => __('subscriptions.plans.fields.billing_period'),
                'type' => 'select_from_array',
                'options' => [
                    'monthly' => __('subscriptions.plans.billing_periods.monthly'),
                    'yearly' => __('subscriptions.plans.billing_periods.yearly'),
                ],
            ],
            [
                'name' => 'billing_interval',
                'label' => __('subscriptions.plans.fields.billing_interval'),
                'type' => 'number',
            ],
            [
                'name' => 'trial_days',
                'label' => __('subscriptions.plans.fields.trial_days'),
                'type' => 'number',
            ],
            [
                'name' => 'is_active',
                'label' => __('subscriptions.plans.fields.is_active'),
                'type' => 'boolean',
                'options' => [
                    0 => __('general.no'),
                    1 => __('general.yes'),
                ],
            ],
        ]);

        // Add filters
        if (backpack_pro()) {
            CRUD::addFilter(
                [
                    'type' => 'simple',
                    'name' => 'is_active',
                    'label' => __('subscriptions.plans.filters.active_only'),
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

            CRUD::addFilter(
                [
                    'name' => 'billing_period',
                    'type' => 'select2',
                    'label' => __('subscriptions.plans.fields.billing_period'),
                ],
                [
                    'monthly' => __('subscriptions.plans.billing_periods.monthly'),
                    'yearly' => __('subscriptions.plans.billing_periods.yearly'),
                ],
                function ($value): void {
                    CRUD::addClause(
                        'where',
                        'billing_period',
                        $value,
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
        CRUD::setValidation(SubscriptionPlanRequest::class);

        CRUD::addFields([
            [
                'name' => 'name',
                'label' => __('subscriptions.plans.fields.name'),
                'type' => 'text',
                'attributes' => [
                    'placeholder' => __('subscriptions.plans.placeholders.name'),
                ],
            ],
            [
                'name' => 'description',
                'label' => __('subscriptions.plans.fields.description'),
                'type' => 'textarea',
                'attributes' => [
                    'placeholder' => __('subscriptions.plans.placeholders.description'),
                ],
            ],
            [
                'name' => 'price',
                'label' => __('subscriptions.plans.fields.price'),
                'type' => 'number',
                'attributes' => [
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => __('subscriptions.plans.placeholders.price'),
                ],
            ],
            [
                'name' => 'currency',
                'label' => __('subscriptions.plans.fields.currency'),
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
                'label' => __('subscriptions.plans.fields.billing_period'),
                'type' => 'select_from_array',
                'options' => [
                    'monthly' => __('subscriptions.plans.billing_periods.monthly'),
                    'yearly' => __('subscriptions.plans.billing_periods.yearly'),
                ],
                'default' => 'monthly',
            ],
            [
                'name' => 'billing_interval',
                'label' => __('subscriptions.plans.fields.billing_interval'),
                'type' => 'number',
                'attributes' => [
                    'min' => '1',
                    'placeholder' => __('subscriptions.plans.placeholders.billing_interval'),
                ],
                'default' => 1,
            ],
            [
                'name' => 'trial_days',
                'label' => __('subscriptions.plans.fields.trial_days'),
                'type' => 'number',
                'attributes' => [
                    'min' => '0',
                    'placeholder' => __('subscriptions.plans.placeholders.trial_days'),
                ],
                'default' => 0,
            ],
            [
                'name' => 'features',
                'entity' => 'features',
                'label' => __('subscriptions.plans.fields.features'),
                'type' => 'checklist',
                'hint' => __('subscriptions.plans.hints.features'),
                'model' => SubscriptionPlanFeature::class,
                'attribute' => 'name',
                'pivot' => true,
                'show_select_all' => true,
                'number_of_columns' => 2,
            ],
            [
                'name' => 'is_active',
                'label' => __('subscriptions.plans.fields.is_active'),
                'type' => 'checkbox',
                'default' => true,
            ],
        ]);
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

        CRUD::addColumn([
            'name' => 'description',
            'label' => __('subscriptions.plans.fields.description'),
            'type' => 'textarea',
        ]);

        CRUD::addColumn([
            'name' => 'features',
            'label' => __('subscriptions.plans.fields.features'),
            'type' => 'relationship',
            'entity' => 'features',
            'attribute' => 'name',
            'limit' => 50,
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => __('subscriptions.plans.fields.created_at'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => __('subscriptions.plans.fields.updated_at'),
            'type' => 'datetime',
        ]);
    }

    /**
     * Store a new subscription plan
     */
    public function store(): JsonResponse|RedirectResponse
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
        if (! empty($features)) {
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
    public function update(): JsonResponse|RedirectResponse
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
            $requestData,
        );

        // Sync the features
        $item->features()->sync($features);

        Alert::success(trans('backpack::crud.update_success'))->flash();

        // Save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }
}
