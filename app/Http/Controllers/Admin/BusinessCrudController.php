<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Party\Services\BusinessApplicationService;
use App\Application\Party\Services\BusinessLogoApplicationService;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Shared\File\DTO\IncomingFile;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\BusinessRequest;
use App\Models\Business;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
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
    use UpdateOperation;

    public function setup(): void
    {
        $this->setupParty(
            Business::class,
            'business',
            'businesses',
        );
    }

    public function store(BusinessApplicationService $businesses, BusinessLogoApplicationService $logos): RedirectResponse
    {
        $request = $this->validatedRequest();
        $business = $businesses->create(
            UserId::fromInt((int) $request->validated('user_id')),
            new BusinessWriteData($request->profile(), $this->storeLogo($request, $logos, null)),
            null,
        );

        Alert::success(trans('backpack::crud.insert_success'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($business->id->value);
    }

    public function update(BusinessApplicationService $businesses, BusinessLogoApplicationService $logos): RedirectResponse
    {
        $request = $this->validatedRequest();
        $entry = $this->entry();
        $logo = $this->storeLogo(
            $request,
            $logos,
            $entry->logo,
        );

        $businesses->update(
            $this->ownerOf($entry),
            BusinessId::fromInt($entry->id),
            new BusinessWriteData($request->profile(), $logo),
        );
        if ($entry->logo !== null && $entry->logo !== $logo) {
            $logos->delete($entry->logo);
        }

        Alert::success(trans('backpack::crud.update_success'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($entry->id);
    }

    public function archive(BusinessApplicationService $businesses, int $id): RedirectResponse
    {
        abort_unless(backpack_user()?->can('can_archive_business') === true, 403);
        $entry = Business::query()->findOrFail($id);
        $businesses->archive(
            $this->ownerOf($entry),
            BusinessId::fromInt($entry->id),
            null,
        );

        return $this->redirectWithSuccess(__('businesses.messages.archived'));
    }

    public function restore(BusinessApplicationService $businesses, int $id): RedirectResponse
    {
        abort_unless(backpack_user()?->can('can_restore_business') === true, 403);
        $entry = Business::query()->findOrFail($id);
        $businesses->restore($this->ownerOf($entry), BusinessId::fromInt($entry->id));

        return $this->redirectWithSuccess(__('businesses.messages.restored'));
    }

    public function setDefault(BusinessApplicationService $businesses, int $id): RedirectResponse
    {
        abort_unless(backpack_user()?->can('can_set_default_business') === true, 403);
        $entry = Business::query()->findOrFail($id);
        $businesses->setDefault($this->ownerOf($entry), BusinessId::fromInt($entry->id));

        return $this->redirectWithSuccess(__('businesses.messages.default_changed'));
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
        CRUD::column('short_name')->label(__('parties.fields.short_name'));
        CRUD::column('vat_number')->label(__('parties.fields.vat_number'));
        CRUD::column('phone')->label(__('parties.fields.phone'));
        CRUD::column('note')->label(__('parties.fields.note'));
        CRUD::column('logo')
            ->label(__('businesses.fields.logo'))
            ->type('image')
            ->disk('public');
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
        CRUD::field('logo')
            ->label(__('businesses.fields.logo'))
            ->type('upload')
            ->hint(__('businesses.hints.logo'));
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

    private function storeLogo(
        BusinessRequest $request,
        BusinessLogoApplicationService $logos,
        ?string $current,
    ): ?string {
        $file = $request->file('logo');
        if (! $file instanceof UploadedFile) {
            return $current;
        }

        return $logos->store(new IncomingFile(
            $file->getClientOriginalName(),
            strtolower($file->getClientOriginalExtension()),
            (string) $file->getMimeType(),
            (int) $file->getSize(),
            (string) $file->getRealPath(),
        ));
    }
}
