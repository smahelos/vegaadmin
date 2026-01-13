<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Requests\Admin\SubscriptionRequest;
use App\Models\User;

/**
 * Subscription CRUD controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SubscriptionCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Subscription::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/subscription');
        CRUD::setEntityNameStrings(
            trans('admin.subscriptions.entity_singular'),
            trans('admin.subscriptions.entity_plural')
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
                'name' => 'id',
                'label' => trans('admin.general.id'),
                'type' => 'number',
            ],
            [
                'name' => 'user',
                'label' => trans('admin.subscriptions.user'),
                'type' => 'relationship',
                'attribute' => 'name',
                'model' => 'App\Models\User',
            ],
            [
                'name' => 'subscriptionPlan',
                'label' => trans('admin.subscriptions.subscription_plan'),
                'type' => 'relationship',
                'attribute' => 'name',
                'model' => 'App\Models\SubscriptionPlan',
            ],
            [
                'name' => 'status',
                'label' => trans('admin.subscriptions.status'),
                'type' => 'select_from_array',
                'options' => [
                    'pending' => trans('admin.subscriptions.status_pending'),
                    'active' => trans('admin.subscriptions.status_active'),
                    'cancelled' => trans('admin.subscriptions.status_cancelled'),
                    'expired' => trans('admin.subscriptions.status_expired'),
                    'past_due' => trans('admin.subscriptions.status_past_due'),
                ],
            ],
            [
                'name' => 'amount',
                'label' => trans('admin.subscriptions.amount'),
                'type' => 'number',
                'decimals' => 2,
                'suffix' => ' CZK',
            ],
            [
                'name' => 'currency',
                'label' => trans('admin.subscriptions.currency'),
                'type' => 'text',
            ],
            [
                'name' => 'starts_at',
                'label' => trans('admin.subscriptions.starts_at'),
                'type' => 'datetime',
            ],
            [
                'name' => 'ends_at',
                'label' => trans('admin.subscriptions.ends_at'),
                'type' => 'datetime',
            ],
            [
                'name' => 'next_billing_at',
                'label' => trans('admin.subscriptions.next_billing_at'),
                'type' => 'datetime',
            ],
        ]);

        // Add filters
        if (backpack_pro()) {
            CRUD::addFilter([
                'name' => 'status',
                'type' => 'select2',
                'label' => trans('admin.subscriptions.status'),
            ],
            [
                'pending' => trans('admin.subscriptions.status_pending'),
                'active' => trans('admin.subscriptions.status_active'),
                'cancelled' => trans('admin.subscriptions.status_cancelled'),
                'expired' => trans('admin.subscriptions.status_expired'),
                'past_due' => trans('admin.subscriptions.status_past_due'),
            ],
            function ($value) {
                CRUD::addClause('where', 'status', $value);
            });

        CRUD::filter('user_id')
            ->type('select2')
            ->label('User')
            ->values(
                User::all()->pluck('name', 'id')->toArray(),
            )
            ->whenActive(function($value) {
                CRUD::addClause('where', 'user_id', $value);
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
        CRUD::setValidation(SubscriptionRequest::class);

        CRUD::addFields([
            [
                'name' => 'user_id',
                'label' => trans('admin.subscriptions.user'),
                'type' => 'select2_from_ajax',
                'model' => 'App\Models\User',
                'attribute' => 'name',
                'data_source' => url('api/admin/user'),
                'placeholder' => trans('admin.subscriptions.select_user'),
                'minimum_input_length' => 2,
            ],
            [
                'name' => 'subscription_plan_id',
                'label' => trans('admin.subscriptions.subscription_plan'),
                'type' => 'select2',
                'model' => 'App\Models\SubscriptionPlan',
                'attribute' => 'name',
                'options' => (function ($query) {
                    return $query->orderBy('name', 'ASC')->get();
                }),
            ],
            [
                'name' => 'status',
                'label' => trans('admin.subscriptions.status'),
                'type' => 'select_from_array',
                'options' => [
                    'pending' => trans('admin.subscriptions.status_pending'),
                    'active' => trans('admin.subscriptions.status_active'),
                    'cancelled' => trans('admin.subscriptions.status_cancelled'),
                    'expired' => trans('admin.subscriptions.status_expired'),
                    'past_due' => trans('admin.subscriptions.status_past_due'),
                ],
                'default' => 'pending',
            ],
            [
                'name' => 'amount',
                'label' => trans('admin.subscriptions.amount'),
                'type' => 'number',
                'attributes' => [
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => trans('admin.subscriptions.amount_placeholder'),
                ],
            ],
            [
                'name' => 'currency',
                'label' => trans('admin.subscriptions.currency'),
                'type' => 'select_from_array',
                'options' => [
                    'CZK' => 'CZK',
                    'EUR' => 'EUR',
                    'USD' => 'USD',
                ],
                'default' => 'CZK',
            ],
            [
                'name' => 'starts_at',
                'label' => trans('admin.subscriptions.starts_at'),
                'type' => 'datetime_picker',
                'datetime_picker_options' => [
                    'format' => 'DD/MM/YYYY HH:mm',
                    'language' => 'cs',
                ],
            ],
            [
                'name' => 'ends_at',
                'label' => trans('admin.subscriptions.ends_at'),
                'type' => 'datetime_picker',
                'datetime_picker_options' => [
                    'format' => 'DD/MM/YYYY HH:mm',
                    'language' => 'cs',
                ],
            ],
            [
                'name' => 'trial_ends_at',
                'label' => trans('admin.subscriptions.trial_ends_at'),
                'type' => 'datetime_picker',
                'datetime_picker_options' => [
                    'format' => 'DD/MM/YYYY HH:mm',
                    'language' => 'cs',
                ],
            ],
            [
                'name' => 'next_billing_at',
                'label' => trans('admin.subscriptions.next_billing_at'),
                'type' => 'datetime_picker',
                'datetime_picker_options' => [
                    'format' => 'DD/MM/YYYY HH:mm',
                    'language' => 'cs',
                ],
            ],
            [
                'name' => 'metadata',
                'label' => trans('admin.subscriptions.metadata'),
                'type' => 'textarea',
                'hint' => trans('admin.subscriptions.metadata_hint'),
                'attributes' => [
                    'placeholder' => trans('admin.subscriptions.metadata_placeholder'),
                ],
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
            'name' => 'trial_ends_at',
            'label' => trans('admin.subscriptions.trial_ends_at'),
            'type' => 'datetime',
        ]);
        
        CRUD::addColumn([
            'name' => 'metadata',
            'label' => trans('admin.subscriptions.metadata'),
            'type' => 'textarea',
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
}
