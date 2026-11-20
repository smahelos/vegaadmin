<?php

namespace App\Http\Controllers\Admin;

use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\User\Enums\Permission;
use App\Http\Requests\Admin\BankRequest;
use App\Infrastructure\Forms\Party\BankFormFields;
use App\Models\Bank;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\App;

/**
 * Class BankCrudController
 *
 * @property-read CrudPanel $crud
 */
class BankCrudController extends CrudController
{
    use CreateOperation;
    use DeleteOperation;
    use GatesCrudOperations;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
        private readonly CountryOptionsPresenter $countries,
    ) {
        parent::__construct();
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup(): void
    {
        CRUD::setModel(Bank::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/bank',
        );
        CRUD::setEntityNameStrings('bank', 'banks');

        $this->allowHeldOperations(
            [
                'list' => Permission::BANK_VIEW,
                'show' => Permission::BANK_VIEW,
                'create' => Permission::BANK_CREATE,
                'update' => Permission::BANK_UPDATE,
                'delete' => Permission::BANK_DELETE,
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
        CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     */
    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(BankRequest::class);

        $countries = $this->countries->options(App::getLocale());
        $this->crud->addFields(
            BankFormFields::fields($countries),
        );
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
}
