<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Invoice\Services\ExpenseApplicationService;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\ExpenseRequest;
use App\Infrastructure\Forms\Invoice\ExpenseFormFields;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Status;
use App\Models\StatusCategory;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\Facades\Alert;

/**
 * Class ExpenseCrudController
 *
 * @property-read CrudPanel $crud
 */
final class ExpenseCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    public function __construct()
    {
        parent::__construct();
        $throttle = $this->middleware('throttle:create');
        $throttle->only('store');
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(Expense::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/expense',
        );
        CRUD::setEntityNameStrings(
            __('expenses.entity.singular'),
            __('expenses.entity.plural'),
        );

        $this->denyOperationsWithout(
            [
                'list' => Permission::EXPENSE_VIEW,
                'show' => Permission::EXPENSE_VIEW,
                'create' => Permission::EXPENSE_CREATE,
                'update' => Permission::EXPENSE_UPDATE,
                'delete' => Permission::EXPENSE_DELETE,
            ],
        );
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     */
    protected function setupListOperation(): void
    {
        $column = CRUD::column('expense_date');
        $column->label(
            __('expenses.fields.expense_date'),
        );
        $column->type('date');

        $column = CRUD::column('reference_number');
        $column->label(
            __('expenses.fields.reference_number'),
        );

        $column = CRUD::column('supplier_name');
        $column->type('text');
        $column->label(
            __('expenses.fields.supplier'),
        );
        $column = CRUD::column('customer_name');
        $column->type('text');
        $column->label(
            __('expenses.fields.customer'),
        );

        $column = CRUD::column('category');
        $column->label(
            __('expenses.fields.category_id'),
        );
        $column->type('relationship');
        $column->attribute('name');

        $column = CRUD::column('amount');
        $column->label(
            __('expenses.fields.amount'),
        );
        $column->type('number');
        $column->prefix('');

        $column = CRUD::column('currency');
        $column->label(
            __('expenses.fields.currency'),
        );

        $column = CRUD::column('status');
        $column->label(
            __('expenses.fields.status_id'),
        );
        $column->type('relationship');
        $column->attribute('name');

        $column = CRUD::column('description');
        $column->label(
            __('expenses.fields.description'),
        );
        $column->type('text');
        $column->limit(80);
        $column->escaped(true);

        $column = CRUD::column('attachments');
        $column->label(
            __('expenses.fields.attachments'),
        );
        $column->type('closure');
        $column->function(
            function ($entry) {
                if (!is_array($entry->attachments)) {
                    return '';
                }
                $fileNames = array_map(
                    function ($path) {
                        return basename($path);
                    },
                    $entry->attachments,
                );

                return implode(', ', $fileNames);
            },
        );
        $column->limit(50);

        // Filters
        $filter = CRUD::filter('expense_date');
        $filter->type('datepicker_range');
        $filter->label(
            __('expenses.filters.date_range'),
        );
        $filter->whenActive(
            function ($value): void {
                $dates = json_decode($value);
                CRUD::addClause(
                    'where',
                    'expense_date',
                    '>=',
                    $dates->from,
                );
                CRUD::addClause(
                    'where',
                    'expense_date',
                    '<=',
                    $dates->to,
                );
            },
        );

        $filter = CRUD::filter('contact_id');
        $filter->type('select2');
        $filter->label(
            __('invoices.fields.contact_id'),
        );
        $contactNames = Contact::pluck('name', 'id');
        $filter->values($contactNames->toArray());
        $filter->whenActive(
            fn ($value) => CRUD::addClause(
                'where',
                'contact_id',
                $value,
            ),
        );

        $filter = CRUD::filter('category_id');
        $filter->type('select2');
        $filter->label(
            __('expenses.fields.category_id'),
        );
        $categoryQuery = ExpenseCategory::where('is_active', 1);
        $categoryNames = $categoryQuery->pluck('name', 'id');
        $filter->values($categoryNames->toArray());

        // Get the category ID for 'expense'
        $statusCategoryQuery = StatusCategory::where('slug', 'expense-statuses');
        $statusCategory = $statusCategoryQuery->first();
        $categoryId = $statusCategory?->id;

        $filter = CRUD::filter('status_id');
        $filter->type('select2');
        $filter->label(
            __('expenses.fields.status_id'),
        );
        $statusQuery = Status::where('category_id', $categoryId);
        $statusQuery->where('is_active', 1);
        $statusNames = $statusQuery->pluck('name', 'id');
        $filter->values($statusNames->toArray());
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ExpenseRequest::class);
        $application = app();
        $presenter = app(CountryOptionsPresenter::class);
        $countries = $presenter->options($application->getLocale());
        CRUD::addFields(
            ExpenseFormFields::fields(
                $countries,
                CRUD::getCurrentOperation() === 'update' ? CRUD::getCurrentEntry() : null,
            ),
        );
        $widget = Widget::add();
        $widget->type('script');
        $widget->content(
            asset('assets/js/admin/forms/expense-tax-calculator.js'),
        );
    }

    public function store(ExpenseApplicationService $expenses): RedirectResponse
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->validatedRequest();
        $expense = $expenses->create(
            UserId::fromInt(
                (int) $request->validated('user_id'),
            ),
            $request->toWriteData(),
            UserId::fromInt((int) backpack_user()->id),
        );
        $alerts = Alert::success(
            __('expenses.messages.created'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($expense->id->value);
    }

    public function update(ExpenseApplicationService $expenses): RedirectResponse
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->validatedRequest();
        $expense = $expenses->update(
            UserId::fromInt(
                (int) $request->validated('user_id'),
            ),
            ExpenseId::fromInt((int) $this->crud->getCurrentEntryId()),
            $request->toWriteData(),
            UserId::fromInt((int) backpack_user()->id),
        );
        $alerts = Alert::success(
            __('expenses.messages.updated'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($expense->id->value);
    }

    private function validatedRequest(): ExpenseRequest
    {
        $request = $this->crud->validateRequest();
        abort_unless($request instanceof ExpenseRequest, 500);

        return $request;
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     */
    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    /**
     * Configure the Show operation.
     */
    protected function setupShowOperation(): void
    {
        $column = CRUD::column('supplier_logo');
        $column->type('image');
        $column->disk('public');
        $column->label(
            __('parties.fields.supplier_logo'),
        );
        // Re-use most of the same columns from list operation
        $this->setupListOperation();

        // Also ensure description is properly displayed if null
        $columns = collect($this->crud->columns());
        if ($columns->firstWhere('name', 'description')) {
            $this->crud->modifyColumn(
                'description',
                [
                    'escaped' => true,
                    'type' => 'closure',
                    'function' => function ($entry) {
                        return $entry->description ?? '';
                    },
                ],
            );
        }
    }
}
