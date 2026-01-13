<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SupplierRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;

/**
 * Supplier management controller
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SupplierCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation; // Usage recording via generic observer

    /**
     * Configure the CrudPanel object
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Supplier::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/supplier');
        CRUD::setEntityNameStrings('supplier', 'suppliers');
    }

    /**
     * Setup list view columns
     *
     * @return void
     */
    protected function setupListOperation()
    {
        // Add filters
        CRUD::filter('user_id')
            ->type('select2')
            ->label('User')
            ->values(
                User::all()->pluck('name', 'id')->toArray(),
            )
            ->whenActive(function($value) {
                CRUD::addClause('where', 'user_id', $value);
            });

        CRUD::column('user_id')
            ->type('integer')
            ->label('Owner')
            ->entity('user')
            ->attribute('name')
            ->value(function($entry) {
                return $entry->user_id ? $entry->user->name : 'N/A';
            });

        CRUD::column('ico')->type('text');
        CRUD::column('name')->type('text')->after('shortcut');
        CRUD::column('phone')->type('text');
        CRUD::column('email')->type('email');
        CRUD::column('country')->type('text');
        CRUD::column('is_default')->type('text');

        // Add supplier logo column
        CRUD::addColumn([
            'name' => 'supplier_logo',
            'label' => __('suppliers.fields.supplier_logo'),
            'type' => 'image',
            'disk' => 'public',
            'height' => '50px',
            'width' => '50px',
        ]);
    }

    /**
     * Setup create form fields
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SupplierRequest::class);
        CRUD::setFromDb();

        // Configure supplier logo field
        CRUD::addField([
            'name' => 'supplier_logo',
            'label' => __('suppliers.fields.supplier_logo'),
            'type' => 'upload',
            'upload' => true,
            'disk' => 'public',
            'prefix' => 'suppliers/logos/',
        ]);
    }

    /**
     * Setup update form fields
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Ensure user_id is always set to the current backpack user before storing.
     */
    protected function beforeEntityStore(): void
    {
        if (backpack_user()) {
            $request = $this->crud->getRequest();
            if (!$request->has('user_id') || empty($request->input('user_id'))) {
                $request->merge(['user_id' => backpack_user()->id]);
            }
        }
    }

    public function store()
    {
        $this->beforeEntityStore();
        return parent::store();
    }

}
