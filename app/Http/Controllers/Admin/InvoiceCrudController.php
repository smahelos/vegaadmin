<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Invoice\Queries\GetIssuedInvoicePdf;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Enums\Permission;
use App\Infrastructure\Forms\User\OwnerFormFields;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Read-only: the owner creates, issues and deletes invoices on the frontend, and an issued invoice changes only
 * by a corrective document (D-050). The admin downloads the PDF stored at issue.
 *
 * @property-read CrudPanel $crud
 */
final class InvoiceCrudController extends CrudController
{
    use GatesCrudOperations;
    use ListOperation;
    use NarrowsCrudTypes;
    use ShowOperation;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {
        parent::__construct();
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
        $this->allowHeldOperations(
            [
                'list' => Permission::INVOICE_VIEW,
                'show' => Permission::INVOICE_VIEW,
            ],
        );
        // The line stack is shown in the list rows and on the detail
        CRUD::addButton(
            'line',
            'invoice_download',
            'view',
            'admin.buttons.invoice_download',
            'end',
        );
    }

    public function download(int $id): Response
    {
        // The route names the same use case in its `allowed` middleware: change them together
        $pdf = $this->useCases->run(
            $this->admin(),
            new GetIssuedInvoicePdf(
                InvoiceId::fromInt($id),
            ),
        );
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $pdf->filename,
        );

        return new Response(
            $pdf->content,
            200,
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => $disposition],
        );
    }

    /**
     * Setup list view columns
     */
    protected function setupListOperation(): void
    {
        // Add filters
        CRUD::addFilter(
            OwnerFormFields::filter(
                __('invoices.fields.user_id'),
            ),
            OwnerFormFields::names(),
        );

        $statuses = [];
        foreach (InvoiceStatus::cases() as $status) {
            $statuses[$status->value] = __('invoices.statuses.' . $status->value);
        }
        CRUD::addFilter(
            [
                'name' => 'payment_status',
                'type' => 'select2',
                'label' => __('invoices.fields.status'),
            ],
            $statuses,
            function (string $value): void {
                // A draft stores a pending status, but has no payment status until it is issued
                CRUD::addClause('whereNotNull', 'issued_at');
                CRUD::addClause(
                    'where',
                    'payment_status',
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
            function (Invoice $entry): string {
                if ($entry->issued_at === null) {
                    return __('invoices.statuses.draft');
                }

                return __('invoices.statuses.' . $entry->payment_status->value);
            },
        );
        // The search would match the stored value, not the label; the status filter covers it.
        $column->searchLogic(false);

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
        $column = CRUD::column('payment_status');
        $column->type('text');
        $column->label(
            __('invoices.fields.status'),
        );
        $column->value(
            function (Invoice $entry): string {
                if ($entry->issued_at === null) {
                    return __('invoices.statuses.draft');
                }

                return __('invoices.statuses.' . $entry->payment_status->value);
            },
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
}
