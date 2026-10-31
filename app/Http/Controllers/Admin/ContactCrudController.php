<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Party\Services\ContactApplicationService;
use App\Application\Party\Services\PartyLogoApplicationService;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\ContactRequest;
use App\Infrastructure\Forms\Party\PartyFormFields;
use App\Models\Contact;
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
 * The users' address books in the admin.
 *
 * @property-read CrudPanel $crud
 */
final class ContactCrudController extends CrudController
{
    use ConfiguresPartyCrud;
    use CreateOperation;
    use ListOperation;
    use ShowOperation;
    use StoresPartyLogo;
    use UpdateOperation;

    public function setup(): void
    {
        $this->setupParty(
            Contact::class,
            'contact',
            'contacts',
        );
    }

    public function store(ContactApplicationService $contacts, PartyLogoApplicationService $logos): RedirectResponse
    {
        $request = $this->validatedRequest();
        $contact = $this->withStoredLogo(
            $request,
            $logos,
            null,
            fn (?string $logo): ContactDTO => $contacts->create(
                UserId::fromInt((int) $request->validated('user_id')),
                new ContactWriteData(
                    $request->writeData()->profile,
                    $request->writeData()->contactPerson,
                    $logo,
                ),
            ),
        );

        Alert::success(__('backpack::crud.insert_success'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($contact->id->value);
    }

    public function update(ContactApplicationService $contacts, PartyLogoApplicationService $logos): RedirectResponse
    {
        $request = $this->validatedRequest();
        $entry = $this->entry();
        $this->withStoredLogo(
            $request,
            $logos,
            $entry->logo,
            fn (?string $logo): ContactDTO => $contacts->update(
                $this->ownerOf($entry),
                ContactId::fromInt($entry->id),
                new ContactWriteData(
                    $request->writeData()->profile,
                    $request->writeData()->contactPerson,
                    $logo,
                ),
            ),
        );

        Alert::success(__('backpack::crud.update_success'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($entry->id);
    }

    public function archive(ContactApplicationService $contacts, int $id): RedirectResponse
    {
        abort_unless(backpack_user()?->can('can_archive_contact') === true, 403);
        $entry = Contact::query()->findOrFail($id);
        $contacts->archive($this->ownerOf($entry), ContactId::fromInt($entry->id));

        return $this->redirectWithSuccess(__('contacts.messages.archived'));
    }

    public function restore(ContactApplicationService $contacts, int $id): RedirectResponse
    {
        abort_unless(backpack_user()?->can('can_restore_contact') === true, 403);
        $entry = Contact::query()->findOrFail($id);
        $contacts->restore($this->ownerOf($entry), ContactId::fromInt($entry->id));

        return $this->redirectWithSuccess(__('contacts.messages.restored'));
    }

    protected function setupListOperation(): void
    {
        $this->setupPartyList('contact');
    }

    protected function setupShowOperation(): void
    {
        $this->setupPartyList('contact');
        CRUD::column('short_name')->label(__('parties.fields.short_name'));
        CRUD::column('vat_number')->label(__('parties.fields.vat_number'));
        CRUD::column('phone')->label(__('parties.fields.phone'));
        CRUD::column('contact_person')->label(__('contacts.fields.contact_person'));
        CRUD::column('note')->label(__('parties.fields.note'));
        CRUD::column('logo')->label(__('parties.fields.logo'))->type('image')->disk('public');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ContactRequest::class);
        $this->addOwnerField();
        $this->addProfileFields();
        $this->addContactPersonField();
        CRUD::addFields(PartyFormFields::logo());
    }

    protected function setupUpdateOperation(): void
    {
        CRUD::setValidation(ContactRequest::class);
        $this->addProfileFields($this->entry()->address);
        $this->addContactPersonField();
        CRUD::addFields(PartyFormFields::logo());
    }

    private function addContactPersonField(): void
    {
        CRUD::field('contact_person')
            ->label(__('contacts.fields.contact_person'))
            ->type('text');
    }

    private function validatedRequest(): ContactRequest
    {
        $request = $this->crud->validateRequest();
        abort_unless($request instanceof ContactRequest, 500);

        return $request;
    }

    private function entry(): Contact
    {
        $entry = $this->crud->getCurrentEntry();
        abort_unless($entry instanceof Contact, 404);

        return $entry;
    }
}
