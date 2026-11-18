<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Payment\Commands\CreateSubscription;
use App\Application\Payment\Commands\DeleteSubscription;
use App\Application\Payment\Commands\UpdateSubscription;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Payment\Exceptions\SubscriptionInUseException;
use App\Domain\Payment\ValueObjects\SubscriptionId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\SubscriptionRequest;
use App\Infrastructure\Forms\User\OwnerFormFields;
use App\Models\Subscription;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\Facades\Alert;

/**
 * Subscription CRUD controller
 *
 * @property-read CrudPanel $crud
 */
final class SubscriptionCrudController extends CrudController
{
    use ActsAsAdmin;
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use NarrowsCrudTypes;
    use ShowOperation;
    use UpdateOperation;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {
        parent::__construct();
    }

    /**
     * Configure the CrudPanel object and check user permissions
     */
    public function setup(): void
    {
        CRUD::setModel(Subscription::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/subscription',
        );
        CRUD::setEntityNameStrings(
            __('subscriptions.entity.singular'),
            __('subscriptions.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => Permission::SUBSCRIPTION_VIEW,
                'show' => Permission::SUBSCRIPTION_VIEW,
                'create' => Permission::SUBSCRIPTION_CREATE,
                'update' => Permission::SUBSCRIPTION_UPDATE,
                'delete' => Permission::SUBSCRIPTION_DELETE,
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
                    'name' => 'id',
                    'label' => __('admin.general.id'),
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
            ],
        );

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
                function ($value): void {
                    CRUD::addClause(
                        'where',
                        'status',
                        $value,
                    );
                },
            );

            CRUD::addFilter(
                OwnerFormFields::filter(
                    __('subscriptions.filters.user_id'),
                ),
                OwnerFormFields::names(),
            );
        }
    }

    /**
     * Setup create and update forms
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(SubscriptionRequest::class);

        CRUD::addFields(
            [
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
                        $query->orderBy('name', 'ASC');

                        return $query->get();
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
            ],
        );
    }

    public function store(): RedirectResponse
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->validatedRequest(SubscriptionRequest::class);
        $subscription = $this->useCases->run(
            $this->admin(),
            new CreateSubscription(
                $request->owner(),
                $request->toWriteData(),
            ),
        );

        return $this->saved('backpack::crud.insert_success', $subscription->id);
    }

    public function update(): RedirectResponse
    {
        $this->crud->hasAccessOrFail('update');
        $stored = $this->currentEntry(Subscription::class);
        // The owner field is disabled on edit, so the stored owner is validated and kept
        $httpRequest = $this->crud->getRequest();
        $httpRequest->merge(['user_id' => $stored->user_id]);
        $request = $this->validatedRequest(SubscriptionRequest::class);
        $subscription = $this->useCases->run(
            $this->admin(),
            new UpdateSubscription(
                $request->owner(),
                SubscriptionId::fromInt((int) $this->crud->getCurrentEntryId()),
                $request->toWriteData(),
            ),
        );

        return $this->saved('backpack::crud.update_success', $subscription->id);
    }

    /**
     * @return string|array{error: list<string>} "1" when deleted; Backpack's delete button shows the notification
     */
    public function destroy(int|string $id): string|array
    {
        $this->crud->hasAccessOrFail('delete');
        $entry = $this->currentEntry(Subscription::class);
        try {
            $this->useCases->run(
                $this->admin(),
                new DeleteSubscription(
                    UserId::fromInt($entry->user_id),
                    SubscriptionId::fromInt($entry->id),
                ),
            );
        } catch (SubscriptionInUseException) {
            return ['error' => [__('general.messages.delete_referenced')]];
        }

        return '1';
    }

    private function saved(string $message, int $id): RedirectResponse
    {
        $alerts = Alert::success(
            __($message),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($id);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
        CRUD::modifyField(
            'user_id',
            ['attributes' => ['disabled' => 'disabled']],
        );
    }

    /**
     * Setup show operation
     */
    protected function setupShowOperation(): void
    {
        $this->setupListOperation();

        CRUD::addColumn(
            [
                'name' => 'trial_ends_at',
                'label' => __('subscriptions.fields.trial_ends_at'),
                'type' => 'datetime',
            ],
        );

        CRUD::addColumn(
            [
                'name' => 'metadata',
                'label' => __('subscriptions.fields.metadata'),
                'type' => 'textarea',
            ],
        );

        CRUD::addColumn(
            [
                'name' => 'created_at',
                'label' => __('subscriptions.fields.created_at'),
                'type' => 'datetime',
            ],
        );

        CRUD::addColumn(
            [
                'name' => 'updated_at',
                'label' => __('subscriptions.fields.updated_at'),
                'type' => 'datetime',
            ],
        );
    }
}
