<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Enums\Permission;
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
    use GatesCrudOperations;
    use ListOperation;
    use NarrowsCrudTypes;
    use ShowOperation;
    use UpdateOperation;

    public function __construct()
    {
        parent::__construct();
        $throttle = $this->middleware('throttle:create');
        $throttle->only('store');
    }

    /**
     * Configure the CrudPanel object
     */
    public function setup(): void
    {
        CRUD::setModel(Invoice::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/invoice',
        );
        CRUD::setEntityNameStrings(
            __('invoices.entity.singular'),
            __('invoices.entity.plural'),
        );
        $this->denyOperationsWithout(
            [
                'list' => Permission::INVOICE_VIEW,
                'show' => Permission::INVOICE_VIEW,
                'create' => Permission::INVOICE_CREATE,
                'update' => Permission::INVOICE_UPDATE,
                'delete' => Permission::INVOICE_DELETE,
            ],
        );
    }

    /**
     * Setup list view columns
     */
    protected function setupListOperation(): void
    {
        // Add filters
        $filter = CRUD::filter('user_id');
        $filter->type('select2');
        $filter->label(
            __('invoices.fields.user_id'),
        );
        $userNames = User::pluck('name', 'id');
        $filter->values($userNames->toArray());
        $filter->whenActive(
            function ($value): void {
                CRUD::addClause(
                    'where',
                    'user_id',
                    $value,
                );
            },
        );

        $filter = CRUD::filter('payment_status_id');
        $filter->type('select2');
        $filter->label(
            __('invoices.fields.status'),
        );
        $statusNames = Status::pluck('name', 'id');
        $filter->values($statusNames->toArray());
        $filter->whenActive(
            function ($value): void {
                CRUD::addClause(
                    'where',
                    'payment_status_id',
                    $value,
                );
            },
        );

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

        $column = CRUD::column('invoice_vs');
        $column->label(
            __('invoices.fields.invoice_vs'),
        );
        $column = CRUD::column('supplier_name');
        $column->type('text');
        $column->label(
            __('invoices.fields.supplier'),
        );
        $column = CRUD::column('customer_name');
        $column->type('text');
        $column->label(
            __('invoices.fields.customer'),
        );
        $column = CRUD::column('payment_amount');
        $column->type('number');
        $column->label(
            __('invoices.fields.payment_amount'),
        );
        $column = CRUD::column('issue_date');
        $column->type('date');
        $column->label(
            __('invoices.fields.issue_date'),
        );
        $column = CRUD::column('tax_point_date');
        $column->type('date');
        $column->label(
            __('invoices.fields.tax_point_date'),
        );

        $column = CRUD::column('payment_status');
        $column->type('text');
        $column->label(
            __('invoices.fields.status'),
        );
        $column->value(
            function ($entry) {
                return $entry->paymentStatus?->name ?? __('general.placeholders.not_available');
            },
        );

        $column = CRUD::column('due_in');
        $column->label(
            __('invoices.fields.due_in'),
        );
    }

    protected function setupShowOperation(): void
    {
        $column = CRUD::column('supplier_logo');
        $column->type('image');
        $column->disk('public');
        $column->label(
            __('parties.fields.supplier_logo'),
        );
        $column = CRUD::column('invoice_vs');
        $column->label(
            __('invoices.fields.invoice_vs'),
        );
        $column = CRUD::column('user_id');
        $column->type('select');
        $column->entity('user');
        $column->attribute('name');
        $column->model(User::class);
        $column->label(
            __('invoices.fields.user_id'),
        );
        $column = CRUD::column('supplier_name');
        $column->type('text');
        $column->label(
            __('invoices.fields.supplier'),
        );
        $column = CRUD::column('customer_name');
        $column->type('text');
        $column->label(
            __('invoices.fields.customer'),
        );
        $column = CRUD::column('issue_date');
        $column->type('date');
        $column->label(
            __('invoices.fields.issue_date'),
        );
        $column = CRUD::column('tax_point_date');
        $column->type('date');
        $column->label(
            __('invoices.fields.tax_point_date'),
        );
        $column = CRUD::column('due_in');
        $column->label(
            __('invoices.fields.due_in'),
        );
        $column = CRUD::column('payment_method_id');
        $column->type('select');
        $column->entity('paymentMethod');
        $column->attribute('name');
        $column->model(PaymentMethod::class);
        $column->label(
            __('invoices.fields.payment_method'),
        );
        $column = CRUD::column('payment_status_id');
        $column->type('select');
        $column->entity('paymentStatus');
        $column->attribute('name');
        $column->model(Status::class);
        $column->label(
            __('invoices.fields.status'),
        );
        $column = CRUD::column('payment_amount');
        $column->type('number');
        $column->decimals(2);
        $column->label(
            __('invoices.fields.payment_amount'),
        );
        $column = CRUD::column('payment_currency');
        $column->label(
            __('invoices.fields.payment_currency'),
        );
        $column = CRUD::column('invoice_ks');
        $column->label(
            __('invoices.fields.invoice_ks'),
        );
        $column = CRUD::column('invoice_ss');
        $column->label(
            __('invoices.fields.invoice_ss'),
        );
        $column = CRUD::column('invoice_text');
        $column->label(
            __('invoices.fields.invoice_text'),
        );
        $column = CRUD::column('template');
        $column->label(
            __('invoices.fields.template'),
        );
        $column = CRUD::column('created_at');
        $column->type('datetime');
        $column->label(
            __('general.common.created_at'),
        );
        $column = CRUD::column('updated_at');
        $column->type('datetime');
        $column->label(
            __('general.common.updated_at'),
        );
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
        $request = $this->validatedRequest(InvoiceRequest::class);
        $result = $invoices->create(
            $request->owner(),
            $request->toWriteData(),
            $request->partySelection(),
            $request->products(),
        );

        return $this->mutationResponse($result);
    }

    public function update(InvoiceMutationApplicationServiceInterface $invoices): RedirectResponse
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->validatedRequest(InvoiceRequest::class);
        $result = $invoices->update(
            $request->owner(),
            InvoiceId::fromInt((int) $this->crud->getCurrentEntryId()),
            $request->toWriteData(),
            $request->partySelection(),
            $request->products(),
        );

        return $this->mutationResponse($result);
    }

    private function setupForm(bool $create): void
    {
        CRUD::setValidation(InvoiceRequest::class);
        $application = app();
        $presenter = app(CountryOptionsPresenter::class);
        $countries = $presenter->options($application->getLocale());
        CRUD::addFields(
            (new AdminInvoiceFormFields)->fields(
                $countries,
                $create ? null : CRUD::getCurrentEntry(),
            ),
        );
    }

    private function mutationResponse(InvoiceActionResult $result): RedirectResponse
    {
        if ($result->status !== InvoiceActionStatus::SUCCESS || $result->invoice === null) {
            $redirector = redirect();
            $redirect = $redirector->back();
            $redirect->withInput();

            return $redirect->withErrors(
                ['invoice' => __($result->message ?? 'invoices.messages.update_failed')],
            );
        }
        $alerts = Alert::success(
            __($result->message),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($result->invoice->id);
    }
}
