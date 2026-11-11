<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Party\Services\ContactApplicationService;
use App\Application\Party\Services\PartyLogoApplicationService;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\ContactRequest;
use App\Infrastructure\Forms\Party\PartyFormFields;
use App\Infrastructure\Forms\User\EntityUsageFormFields;
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
    use NarrowsCrudTypes;
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
            Contact::class,
            'contact',
            'contacts',
        );
    }

    public function store(ContactApplicationService $contacts, PartyLogoApplicationService $logos): RedirectResponse
    {
        $request = $this->validatedRequest(ContactRequest::class);
        $contact = $this->withStoredLogo(
            $request,
            $logos,
            null,
            fn (?string $logo): ContactDTO => $contacts->create(
                UserId::fromInt(
                    (int) $request->validated('user_id'),
                ),
                new ContactWriteData(
                    $request->writeData()->profile,
                    $request->writeData()->contactPerson,
                    $logo,
                ),
            ),
        );

        $alerts = Alert::success(
            __('backpack::crud.insert_success'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($contact->id->value);
    }

    public function update(ContactApplicationService $contacts, PartyLogoApplicationService $logos): RedirectResponse
    {
        $request = $this->validatedRequest(ContactRequest::class);
        $entry = $this->currentEntry(Contact::class);
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

        $alerts = Alert::success(
            __('backpack::crud.update_success'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($entry->id);
    }

    public function archive(ContactApplicationService $contacts, int $id): RedirectResponse
    {
        $user = backpack_user();
        abort_unless(
            $user?->can(Permission::CONTACT_ARCHIVE) === true,
            403,
        );
        $contactQuery = Contact::query();
        $entry = $contactQuery->findOrFail($id);
        $contacts->archive(
            $this->ownerOf($entry),
            ContactId::fromInt($entry->id),
        );

        return $this->redirectWithSuccess(
            __('contacts.messages.archived'),
        );
    }

    public function restore(ContactApplicationService $contacts, int $id): RedirectResponse
    {
        $user = backpack_user();
        abort_unless(
            $user?->can(Permission::CONTACT_RESTORE) === true,
            403,
        );
        $contactQuery = Contact::query();
        $entry = $contactQuery->findOrFail($id);
        $contacts->restore(
            $this->ownerOf($entry),
            ContactId::fromInt($entry->id),
        );

        return $this->redirectWithSuccess(
            __('contacts.messages.restored'),
        );
    }

    protected function setupListOperation(): void
    {
        $this->setupPartyList('contact');
    }

    protected function setupShowOperation(): void
    {
        $this->setupPartyList('contact');
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
        $column = CRUD::column('contact_person');
        $column->label(
            __('contacts.fields.contact_person'),
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
        CRUD::setValidation(ContactRequest::class);
        $this->addOwnerField();
        CRUD::addField(
            EntityUsageFormFields::field(LimitedEntity::CONTACT),
        );
        $this->addProfileFields();
        $this->addContactPersonField();
        CRUD::addFields(PartyFormFields::logo());
    }

    protected function setupUpdateOperation(): void
    {
        CRUD::setValidation(ContactRequest::class);
        CRUD::addField(
            EntityUsageFormFields::field(LimitedEntity::CONTACT),
        );
        $this->addProfileFields(
            $this->currentEntry(Contact::class)->address,
        );
        $this->addContactPersonField();
        CRUD::addFields(PartyFormFields::logo());
    }

    private function addContactPersonField(): void
    {
        $field = CRUD::field('contact_person');
        $field->label(
            __('contacts.fields.contact_person'),
        );
        $field->type('text');
    }
}
