<?php

namespace App\Http\Controllers\Admin;

use App\Application\User\Contracts\CrudAccessServiceInterface;
use App\Http\Requests\Admin\SubscriptionRequest;
use App\Models\Subscription;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Subscription CRUD controller
 *
 * @property-read CrudPanel $crud
 */
class SubscriptionCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;
    // Access control is configured via application CrudAccessServiceInterface in setup()

    /**
     * Configure the CrudPanel object and check user permissions
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Subscription::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/subscription');
        CRUD::setEntityNameStrings(
            __('subscriptions.entity.singular'),
            __('subscriptions.entity.plural'),
        );

        // Configure access using centralized service
        $userId = optional(backpack_user())->id ?? 0;
        app(CrudAccessServiceInterface::class)
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
                'label' => __('subscriptions.fields.user_id'),
                'type' => 'relationship',
                'attribute' => 'name',
                'model' => 'App\Models\User',
            ],
            [
                'name' => 'subscriptionPlan',
                'label' => __('subscriptions.fields.subscription_plan_id'),
                'type' => 'relationship',
                'attribute' => 'name',
                'model' => 'App\Models\SubscriptionPlan',
            ],
            [
                'name' => 'status',
                'label' => __('subscriptions.fields.status'),
                'type' => 'select_from_array',
                'options' => [
                    'pending' => __('subscriptions.statuses.pending'),
                    'active' => __('subscriptions.statuses.active'),
                    'cancelled' => __('subscriptions.statuses.cancelled'),
                    'expired' => __('subscriptions.statuses.expired'),
                    'past_due' => __('subscriptions.statuses.past_due'),
                ],
            ],
            [
                'name' => 'amount',
                'label' => __('subscriptions.fields.amount'),
                'type' => 'number',
                'decimals' => 2,
                'suffix' => ' CZK',
            ],
            [
                'name' => 'currency',
                'label' => __('subscriptions.fields.currency'),
                'type' => 'text',
            ],
            [
                'name' => 'starts_at',
                'label' => __('subscriptions.fields.starts_at'),
                'type' => 'datetime',
            ],
            [
                'name' => 'ends_at',
                'label' => __('subscriptions.fields.ends_at'),
                'type' => 'datetime',
            ],
            [
                'name' => 'next_billing_at',
                'label' => __('subscriptions.fields.next_billing_at'),
                'type' => 'datetime',
            ],
        ]);

        // Add filters
        if (backpack_pro()) {
            CRUD::addFilter(
                [
                    'name' => 'status',
                    'type' => 'select2',
                    'label' => __('subscriptions.fields.status'),
                ],
                [
                    'pending' => __('subscriptions.statuses.pending'),
                    'active' => __('subscriptions.statuses.active'),
                    'cancelled' => __('subscriptions.statuses.cancelled'),
                    'expired' => __('subscriptions.statuses.expired'),
                    'past_due' => __('subscriptions.statuses.past_due'),
                ],
                function ($value) {
                    CRUD::addClause(
                        'where',
                        'status',
                        $value,
                    );
                },
            );

            CRUD::filter('user_id')
                ->type('select2')
                ->label(__('subscriptions.filters.user_id'))
                ->values(
                    User::pluck('name', 'id')->toArray(),
                )
                ->whenActive(function ($value) {
                    CRUD::addClause(
                        'where',
                        'user_id',
                        $value,
                    );
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
                'label' => __('subscriptions.fields.user_id'),
                'type' => 'select2_from_ajax',
                'model' => 'App\Models\User',
                'attribute' => 'name',
                'data_source' => url('api/admin/user/search'),
                'placeholder' => __('subscriptions.placeholders.user_id'),
                'minimum_input_length' => 2,
            ],
            [
                'name' => 'subscription_plan_id',
                'label' => __('subscriptions.fields.subscription_plan_id'),
                'type' => 'select2',
                'model' => 'App\Models\SubscriptionPlan',
                'attribute' => 'name',
                'options' => (function ($query) {
                    return $query->orderBy('name', 'ASC')->get();
                }),
            ],
            [
                'name' => 'status',
                'label' => __('subscriptions.fields.status'),
                'type' => 'select_from_array',
                'options' => [
                    'pending' => __('subscriptions.statuses.pending'),
                    'active' => __('subscriptions.statuses.active'),
                    'cancelled' => __('subscriptions.statuses.cancelled'),
                    'expired' => __('subscriptions.statuses.expired'),
                    'past_due' => __('subscriptions.statuses.past_due'),
                ],
                'default' => 'pending',
            ],
            [
                'name' => 'amount',
                'label' => __('subscriptions.fields.amount'),
                'type' => 'number',
                'attributes' => [
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => __('subscriptions.placeholders.amount'),
                ],
            ],
            [
                'name' => 'currency',
                'label' => __('subscriptions.fields.currency'),
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
                'label' => __('subscriptions.fields.starts_at'),
                'type' => 'datepicker',
                'datepicker_options' => ['timepicker' => true],
            ],
            [
                'name' => 'ends_at',
                'label' => __('subscriptions.fields.ends_at'),
                'type' => 'datepicker',
                'datepicker_options' => ['timepicker' => true],
            ],
            [
                'name' => 'trial_ends_at',
                'label' => __('subscriptions.fields.trial_ends_at'),
                'type' => 'datepicker',
                'datepicker_options' => ['timepicker' => true],
            ],
            [
                'name' => 'next_billing_at',
                'label' => __('subscriptions.fields.next_billing_at'),
                'type' => 'datepicker',
                'datepicker_options' => ['timepicker' => true],
            ],
            [
                'name' => 'metadata',
                'label' => __('subscriptions.fields.metadata'),
                'type' => 'textarea',
                'hint' => __('subscriptions.hints.metadata'),
                'attributes' => [
                    'placeholder' => __('subscriptions.placeholders.metadata'),
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
            'label' => __('subscriptions.fields.trial_ends_at'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'metadata',
            'label' => __('subscriptions.fields.metadata'),
            'type' => 'textarea',
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => __('subscriptions.fields.created_at'),
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => __('subscriptions.fields.updated_at'),
            'type' => 'datetime',
        ]);
    }
}
