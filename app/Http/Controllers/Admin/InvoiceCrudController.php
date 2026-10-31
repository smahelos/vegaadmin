<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Infrastructure\Forms\Invoice\AdminInvoiceFormFields;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
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
 * Invoice management controller
 *
 * @property-read CrudPanel $crud
 */
final class InvoiceCrudController extends CrudController
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
        CRUD::setModel(Invoice::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/invoice');
        CRUD::setEntityNameStrings(__('invoices.entity.singular'), __('invoices.entity.plural'));
        foreach ([
            'list' => 'view',
            'show' => 'view',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
        ] as $operation => $permission) {
            if (backpack_user()?->can('can_' . $permission . '_invoice') !== true) {
                CRUD::denyAccess($operation);
            }
        }
    }

    /**
     * Setup list view columns
     */
    protected function setupListOperation(): void
    {
        // Add filters
        CRUD::filter('user_id')
            ->type('select2')
            ->label(__('invoices.fields.user_id'))
            ->values(
                User::pluck('name', 'id')->toArray(),
            )
            ->whenActive(function ($value): void {
                CRUD::addClause(
                    'where',
                    'user_id',
                    $value,
                );
            });

        CRUD::filter('payment_status_id')
            ->type('select2')
            ->label(__('invoices.fields.status'))
            ->values(
                Status::pluck('name', 'id')->toArray(),
            )
            ->whenActive(function ($value): void {
                CRUD::addClause(
                    'where',
                    'payment_status_id',
                    $value,
                );
            });

        CRUD::addFilter(
            [
                'type' => 'datepicker_range',
                'name' => 'tax_point_date',
                'label' => __('invoices.fields.tax_point_date'),
            ],
            false,
            function ($value): void {
                $dates = json_decode($value);
                CRUD::addClause(
                    'where',
                    'tax_point_date',
                    '>=',
                    $dates->from,
                );
                CRUD::addClause(
                    'where',
                    'tax_point_date',
                    '<=',
                    $dates->to,
                );
            },
        );

        CRUD::column('invoice_vs')->label(__('invoices.fields.invoice_vs'));
        CRUD::column('supplier_name')->type('text')->label(__('invoices.fields.supplier'));
        CRUD::column('customer_name')->type('text')->label(__('invoices.fields.customer'));
        CRUD::column('payment_amount')->type('number')->label(__('invoices.fields.payment_amount'));
        CRUD::column('issue_date')->type('date')->label(__('invoices.fields.issue_date'));
        CRUD::column('tax_point_date')->type('date')->label(__('invoices.fields.tax_point_date'));

        CRUD::column('payment_status')
            ->type('text')
            ->label(__('invoices.fields.status'))
            ->value(function ($entry) {
                return $entry->paymentStatus?->name ?? __('general.placeholders.not_available');
            });

        CRUD::column('due_in')->label(__('invoices.fields.due_in'));
    }

    protected function setupShowOperation(): void
    {
        CRUD::column('supplier_logo')->type('image')->disk('public')->label(__('parties.fields.supplier_logo'));
        CRUD::column('invoice_vs')->label(__('invoices.fields.invoice_vs'));
        CRUD::column('user_id')->type('select')->entity('user')->attribute('name')->model(User::class)
            ->label(__('invoices.fields.user_id'));
        CRUD::column('supplier_name')->type('text')->label(__('invoices.fields.supplier'));
        CRUD::column('customer_name')->type('text')->label(__('invoices.fields.customer'));
        CRUD::column('issue_date')->type('date')->label(__('invoices.fields.issue_date'));
        CRUD::column('tax_point_date')->type('date')->label(__('invoices.fields.tax_point_date'));
        CRUD::column('due_in')->label(__('invoices.fields.due_in'));
        CRUD::column('payment_method_id')->type('select')->entity('paymentMethod')->attribute('name')->model(PaymentMethod::class)
            ->label(__('invoices.fields.payment_method'));
        CRUD::column('payment_status_id')->type('select')->entity('paymentStatus')->attribute('name')->model(Status::class)
            ->label(__('invoices.fields.status'));
        CRUD::column('payment_amount')->type('number')->decimals(2)->label(__('invoices.fields.payment_amount'));
        CRUD::column('payment_currency')->label(__('invoices.fields.payment_currency'));
        CRUD::column('invoice_ks')->label(__('invoices.fields.invoice_ks'));
        CRUD::column('invoice_ss')->label(__('invoices.fields.invoice_ss'));
        CRUD::column('invoice_text')->label(__('invoices.fields.invoice_text'));
        CRUD::column('template')->label(__('invoices.fields.template'));
        CRUD::column('created_at')->type('datetime')->label(__('general.common.created_at'));
        CRUD::column('updated_at')->type('datetime')->label(__('general.common.updated_at'));
    }

    protected function setupCreateOperation(): void
    {
        $this->setupForm(true);
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupForm(false);
    }

    public function store(InvoiceMutationApplicationServiceInterface $invoices): RedirectResponse
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->validatedRequest();
        $result = $invoices->create(
            (int) $request->validated('user_id'),
            $request->validated(),
            $request->partySelection(),
            $request->products() ?? [],
            UserId::fromInt((int) backpack_user()->id),
        );

        return $this->mutationResponse($result);
    }

    public function update(InvoiceMutationApplicationServiceInterface $invoices): RedirectResponse
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->validatedRequest();
        $result = $invoices->update(
            (int) $request->validated('user_id'),
            (int) $this->crud->getCurrentEntryId(),
            $request->validated(),
            $request->partySelection(),
            $request->products(),
        );

        return $this->mutationResponse($result);
    }

    private function setupForm(bool $create): void
    {
        CRUD::setValidation(InvoiceRequest::class);
        CRUD::addFields((new AdminInvoiceFormFields)->fields(
            app(CountryOptionsPresenter::class)->options(app()->getLocale()),
            $create ? null : CRUD::getCurrentEntry(),
        ));
    }

    private function validatedRequest(): InvoiceRequest
    {
        $request = $this->crud->validateRequest();
        abort_unless($request instanceof InvoiceRequest, 500);

        return $request;
    }

    private function mutationResponse(InvoiceActionResult $result): RedirectResponse
    {
        if ($result->status !== InvoiceActionStatus::SUCCESS || $result->invoice === null) {
            return redirect()->back()->withInput()->withErrors(['invoice' => __($result->message ?? 'invoices.messages.update_failed')]);
        }
        Alert::success(__($result->message))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($result->invoice->id);
    }
}
