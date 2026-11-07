<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Party\Services\BusinessApplicationService;
use App\Application\Party\Services\PartyLogoApplicationService;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\ArchiveBusinessRequest;
use App\Http\Requests\Admin\BusinessRequest;
use App\Infrastructure\Forms\Party\PartyFormFields;
use App\Models\Business;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\Facades\Alert;

/**
 * The users' own businesses in the admin.
 *
 * @property-read CrudPanel $crud
 */
final class BusinessCrudController extends CrudController
{
    use ConfiguresPartyCrud;
    use CreateOperation;
    use ListOperation;
    use ShowOperation;
    use StoresPartyLogo;
    use UpdateOperation;

    public function __construct()
    {
        parent::__construct();
        $throttle = $this->middleware('throttle:create');
        $throttle->only('store');
    }

    public function setup(): void
    {
        $this->setupParty(
            Business::class,
            'business',
            'businesses',
        );
    }

    public function store(BusinessApplicationService $businesses, PartyLogoApplicationService $logos): RedirectResponse
    {
        $request = $this->validatedRequest();
        $business = $this->withStoredLogo(
            $request,
            $logos,
            null,
            fn (?string $logo): BusinessDTO => $businesses->create(
                UserId::fromInt(
                    (int) $request->validated('user_id'),
                ),
                new BusinessWriteData($request->profile(), $logo),
                null,
            ),
        );

        $alerts = Alert::success(
            __('backpack::crud.insert_success'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($business->id->value);
    }

    public function update(BusinessApplicationService $businesses, PartyLogoApplicationService $logos): RedirectResponse
    {
        $request = $this->validatedRequest();
        $entry = $this->entry();
        $this->withStoredLogo(
            $request,
            $logos,
            $entry->logo,
            fn (?string $logo): BusinessDTO => $businesses->update(
                $this->ownerOf($entry),
                BusinessId::fromInt($entry->id),
                new BusinessWriteData($request->profile(), $logo),
            ),
        );

        $alerts = Alert::success(
            __('backpack::crud.update_success'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($entry->id);
    }

    public function archive(
        ArchiveBusinessRequest $request,
        BusinessApplicationService $businesses,
        int $id,
    ): RedirectResponse {
        $user = backpack_user();
        abort_unless(
            $user?->can('can_archive_business') === true,
            403,
        );
        $businessQuery = Business::query();
        $entry = $businessQuery->findOrFail($id);
        $businesses->archive(
            $this->ownerOf($entry),
            BusinessId::fromInt($entry->id),
            $request->replacement(),
        );

        return $this->redirectWithSuccess(
            __('businesses.messages.archived'),
        );
    }

    public function restore(BusinessApplicationService $businesses, int $id): RedirectResponse
    {
        $user = backpack_user();
        abort_unless(
            $user?->can('can_restore_business') === true,
            403,
        );
        $businessQuery = Business::query();
        $entry = $businessQuery->findOrFail($id);
        $businesses->restore(
            $this->ownerOf($entry),
            BusinessId::fromInt($entry->id),
        );

        return $this->redirectWithSuccess(
            __('businesses.messages.restored'),
        );
    }

    public function setDefault(BusinessApplicationService $businesses, int $id): RedirectResponse
    {
        $user = backpack_user();
        abort_unless(
            $user?->can('can_set_default_business') === true,
            403,
        );
        $businessQuery = Business::query();
        $entry = $businessQuery->findOrFail($id);
        $businesses->setDefault(
            $this->ownerOf($entry),
            BusinessId::fromInt($entry->id),
        );

        return $this->redirectWithSuccess(
            __('businesses.messages.default_changed'),
        );
    }

    protected function setupListOperation(): void
    {
        $this->setupPartyList('business');
        CRUD::addButton(
            'line',
            'business_default',
            'view',
            'admin.buttons.business_default',
            'end',
        );
    }

    protected function setupShowOperation(): void
    {
        $this->setupPartyList('business');
        $column = CRUD::column('short_name');
        $column->label(
            __('parties.fields.short_name'),
        );
        $column = CRUD::column('vat_number');
        $column->label(
            __('parties.fields.vat_number'),
        );
        $column = CRUD::column('phone');
        $column->label(
            __('parties.fields.phone'),
        );
        $column = CRUD::column('note');
        $column->label(
            __('parties.fields.note'),
        );
        $column = CRUD::column('logo');
        $column->label(
            __('parties.fields.logo'),
        );
        $column->type('image');
        $column->disk('public');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(BusinessRequest::class);
        $this->addOwnerField();
        $this->addProfileFields();
        $this->addLogoField();
    }

    protected function setupUpdateOperation(): void
    {
        CRUD::setValidation(BusinessRequest::class);
        $this->addProfileFields($this->entry()->address);
        $this->addLogoField();
    }

    private function addLogoField(): void
    {
        CRUD::addFields(PartyFormFields::logo());
    }

    private function validatedRequest(): BusinessRequest
    {
        $request = $this->crud->validateRequest();
        abort_unless($request instanceof BusinessRequest, 500);

        return $request;
    }

    private function entry(): Business
    {
        $entry = $this->crud->getCurrentEntry();
        abort_unless($entry instanceof Business, 404);

        return $entry;
    }
}
