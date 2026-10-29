<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Invoice\Services\ExpenseApplicationService;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Domain\Invoice\ValueObjects\ExpenseId;
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
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(Expense::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense');
        CRUD::setEntityNameStrings(
            __('expenses.entity.singular'),
            __('expenses.entity.plural'),
        );

        foreach ([
            'list' => 'view',
            'show' => 'view',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
        ] as $operation => $permission) {
            if (backpack_user()?->can('can_' . $permission . '_expense') !== true) {
                CRUD::denyAccess($operation);
            }
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     */
    protected function setupListOperation(): void
    {
        CRUD::column('expense_date')
            ->label(__('expenses.fields.expense_date'))
            ->type('date');

        CRUD::column('reference_number')
            ->label(__('expenses.fields.reference_number'));

        CRUD::column('supplier_name')->type('text')->label(__('expenses.fields.supplier'));
        CRUD::column('customer_name')->type('text')->label(__('expenses.fields.customer'));

        CRUD::column('category')
            ->label(__('expenses.fields.category_id'))
            ->type('relationship')
            ->attribute('name');

        CRUD::column('amount')
            ->label(__('expenses.fields.amount'))
            ->type('number')
            ->prefix('');

        CRUD::column('currency')
            ->label(__('expenses.fields.currency'));

        CRUD::column('status')
            ->label(__('expenses.fields.status_id'))
            ->type('relationship')
            ->attribute('name');

        CRUD::column('description')
            ->label(__('expenses.fields.description'))
            ->type('text')
            ->limit(80)
            ->escaped(true);

        CRUD::column('attachments')
            ->label(__('expenses.fields.attachments'))
            ->type('closure')
            ->function(function ($entry) {
                if (! is_array($entry->attachments)) {
                    return '';
                }
                $fileNames = array_map(function ($path) {
                    return basename($path);
                }, $entry->attachments);

                return implode(', ', $fileNames);
            })
            ->limit(50);

        // Filters
        CRUD::filter('expense_date')
            ->type('datepicker_range')
            ->label(__('expenses.filters.date_range'))
            ->whenActive(function ($value): void {
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
            });

        CRUD::filter('contact_id')
            ->type('select2')
            ->label(__('invoices.fields.contact_id'))
            ->values(Contact::pluck('name', 'id')->toArray())
            ->whenActive(fn ($value) => CRUD::addClause('where', 'contact_id', $value));

        CRUD::filter('category_id')
            ->type('select2')
            ->label(__('expenses.fields.category_id'))
            ->values(ExpenseCategory::where('is_active', 1)
                ->pluck('name', 'id')->toArray());

        // Get the category ID for 'expense'
        $categoryId = StatusCategory::where('slug', 'expense-statuses')->first()->id ?? null;

        CRUD::filter('status_id')
            ->type('select2')
            ->label(__('expenses.fields.status_id'))
            ->values(Status::where('category_id', $categoryId)->where('is_active', 1)
                ->pluck('name', 'id')->toArray());
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ExpenseRequest::class);
        CRUD::addFields(ExpenseFormFields::fields(
            app(CountryOptionsPresenter::class)->options(app()->getLocale()),
            CRUD::getCurrentOperation() === 'update' ? CRUD::getCurrentEntry() : null,
        ));
        Widget::add()->type('script')->content(asset('assets/js/admin/forms/expense-tax-calculator.js'));
    }

    public function store(ExpenseApplicationService $expenses): RedirectResponse
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->validatedRequest();
        $expense = $expenses->create(
            UserId::fromInt((int) $request->validated('user_id')),
            $request->toWriteData(),
            UserId::fromInt((int) backpack_user()->id),
        );
        Alert::success(__('expenses.messages.created'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($expense->id->value);
    }

    public function update(ExpenseApplicationService $expenses): RedirectResponse
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->validatedRequest();
        $expense = $expenses->update(
            UserId::fromInt((int) $request->validated('user_id')),
            ExpenseId::fromInt((int) $this->crud->getCurrentEntryId()),
            $request->toWriteData(),
            UserId::fromInt((int) backpack_user()->id),
        );
        Alert::success(__('expenses.messages.updated'))->flash();
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
        CRUD::column('supplier_logo')->type('image')->disk('public')->label(__('parties.fields.supplier_logo'));
        // Re-use most of the same columns from list operation
        $this->setupListOperation();

        // Also ensure description is properly displayed if null
        if (collect($this->crud->columns())->firstWhere('name', 'description')) {
            $this->crud->modifyColumn('description', [
                'escaped' => true,
                'type' => 'closure',
                'function' => function ($entry) {
                    return $entry->description ?? '';
                },
            ]);
        }
    }
}
