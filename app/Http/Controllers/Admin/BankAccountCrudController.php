<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Party\Services\BankAccountApplicationService;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Requests\Admin\BankAccountRequest;
use App\Infrastructure\Forms\Party\PartyFormFields;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\Facades\Alert;

/**
 * Bank accounts of a business or a contact in the admin, opened from the owner's list. Changing them falls under
 * the owner's update permission and goes through the application service with the owner's user.
 *
 * @property-read CrudPanel $crud
 */
final class BankAccountCrudController extends CrudController
{
    use CreateOperation;
    use ListOperation;
    use UpdateOperation {
        edit as private editAccount;
    }

    public function setup(): void
    {
        CRUD::setModel(BankAccount::class);
        CRUD::setRoute(
            config('backpack.base.route_prefix') . '/bank-account',
        );
        CRUD::setEntityNameStrings(
            __('parties.bank_accounts.entity.singular'),
            __('parties.bank_accounts.entity.plural'),
        );

        $user = backpack_user();
        $denied = [];
        if ($user?->can(Permission::BUSINESS_VIEW) !== true && $user?->can(Permission::CONTACT_VIEW) !== true) {
            $denied[] = 'list';
        }

        if ($user?->can(Permission::BUSINESS_UPDATE) !== true && $user?->can(Permission::CONTACT_UPDATE) !== true) {
            $denied[] = 'create';
            $denied[] = 'update';
        }

        CRUD::denyAccess($denied);
    }

    public function edit(int $id): View
    {
        $accountQuery = BankAccount::query();
        $entry = $accountQuery->findOrFail($id);
        $this->authorizeOwner($entry);

        return $this->editAccount($id);
    }

    public function store(BankAccountApplicationService $accounts): RedirectResponse
    {
        $request = $this->validatedRequest();
        $owner = $this->ownerFromRequest($request);
        $account = $accounts->add(
            $owner['user'],
            $owner['owner'],
            $request->writeData(),
        );

        $alerts = Alert::success(
            __('backpack::crud.insert_success'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($account->id->value);
    }

    public function update(BankAccountApplicationService $accounts): RedirectResponse
    {
        $request = $this->validatedRequest();
        $entry = $this->entry();
        $this->authorizeOwner($entry);
        $accounts->update(
            $this->userOf($entry),
            $this->ownerOf($entry),
            BankAccountId::fromInt($entry->id),
            $request->writeData(),
        );

        $alerts = Alert::success(
            __('backpack::crud.update_success'),
        );
        $alerts->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($entry->id);
    }

    public function archive(BankAccountApplicationService $accounts, int $id): RedirectResponse
    {
        $accountQuery = BankAccount::query();
        $entry = $accountQuery->findOrFail($id);
        $this->authorizeOwner($entry);
        $accounts->archive(
            $this->userOf($entry),
            $this->ownerOf($entry),
            BankAccountId::fromInt($entry->id),
            null,
        );
        $alerts = Alert::success(
            __('parties.bank_accounts.messages.archived'),
        );
        $alerts->flash();

        $redirector = redirect();

        return $redirector->back();
    }

    public function restore(BankAccountApplicationService $accounts, int $id): RedirectResponse
    {
        $accountQuery = BankAccount::query();
        $entry = $accountQuery->findOrFail($id);
        $this->authorizeOwner($entry);
        $accounts->restore(
            $this->userOf($entry),
            $this->ownerOf($entry),
            BankAccountId::fromInt($entry->id),
        );
        $alerts = Alert::success(
            __('parties.bank_accounts.messages.restored'),
        );
        $alerts->flash();

        $redirector = redirect();

        return $redirector->back();
    }

    public function setDefault(BankAccountApplicationService $accounts, int $id): RedirectResponse
    {
        $accountQuery = BankAccount::query();
        $entry = $accountQuery->findOrFail($id);
        $this->authorizeOwner($entry);
        $accounts->setDefault(
            $this->userOf($entry),
            $this->ownerOf($entry),
            BankAccountId::fromInt($entry->id),
        );
        $alerts = Alert::success(
            __('parties.bank_accounts.messages.default_changed'),
        );
        $alerts->flash();

        $redirector = redirect();

        return $redirector->back();
    }

    protected function setupListOperation(): void
    {
        $user = backpack_user();
        if ($user?->can(Permission::BUSINESS_VIEW) !== true) {
            CRUD::addClause('whereNull', 'business_id');
        }
        if ($user?->can(Permission::CONTACT_VIEW) !== true) {
            CRUD::addClause('whereNull', 'contact_id');
        }
        $request = request();
        $businessId = $request->integer('business_id');
        $contactId = $request->integer('contact_id');
        if ($businessId > 0) {
            CRUD::addClause(
                'where',
                'business_id',
                $businessId,
            );
        } elseif ($contactId > 0) {
            CRUD::addClause(
                'where',
                'contact_id',
                $contactId,
            );
        }

        $column = CRUD::column('owner');
        $column->label(
            __('parties.bank_accounts.fields.owner'),
        );
        $column->type('closure');
        $column->function(
            fn (BankAccount $entry): string => e($entry->business->name ?? $entry->contact->name ?? ''),
        );
        $column->escaped(false);
        $column = CRUD::column('label');
        $column->label(
            __('parties.bank_accounts.fields.label'),
        );
        $column = CRUD::column('iban');
        $column->label(
            __('parties.bank_accounts.fields.iban'),
        );
        $column = CRUD::column('number');
        $column->label(
            __('parties.bank_accounts.fields.number'),
        );
        $column = CRUD::column('bank_code');
        $column->label(
            __('parties.bank_accounts.fields.bank_code'),
        );
        $column = CRUD::column('default');
        $column->label(
            __('parties.bank_accounts.fields.default'),
        );
        $column->type('closure');
        $column->function(
            function (BankAccount $entry): string {
                if ($this->isDefault($entry)) {
                    return e(
                        __('general.yes'),
                    );
                }

                return '';
            },
        );
        $column->escaped(false);
        $column = CRUD::column('archived_at');
        $column->label(
            __('parties.statuses.archived'),
        );
        $column->type('datetime');

        CRUD::addButton(
            'line',
            'bank_account_actions',
            'view',
            'admin.buttons.bank_account_actions',
            'end',
        );
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(BankAccountRequest::class);
        $field = CRUD::field('business_id');
        $field->type('hidden');
        $request = request();
        $businessId = $request->integer('business_id');
        $field->value($businessId ?: null);
        $field = CRUD::field('contact_id');
        $field->type('hidden');
        $contactId = $request->integer('contact_id');
        $field->value($contactId ?: null);
        $this->addAccountFields();
    }

    protected function setupUpdateOperation(): void
    {
        CRUD::setValidation(BankAccountRequest::class);
        $this->addAccountFields();
    }

    private function addAccountFields(): void
    {
        $application = app();
        $presenter = app(CountryOptionsPresenter::class);
        $countries = $presenter->options($application->getLocale());

        CRUD::addFields(
            PartyFormFields::bankAccount($countries),
        );
    }

    /**
     * @return array{user: UserId, owner: AccountOwner}
     */
    private function ownerFromRequest(BankAccountRequest $request): array
    {
        $businessId = $request->integer('business_id');
        if ($businessId > 0) {
            $businessQuery = Business::query();
            $business = $businessQuery->findOrFail($businessId);

            return [
                'user' => UserId::fromInt($business->user_id),
                'owner' => AccountOwner::business(
                    BusinessId::fromInt($business->id),
                ),
            ];
        }

        $contactQuery = Contact::query();
        $contact = $contactQuery->findOrFail(
            $request->integer('contact_id'),
        );

        return [
            'user' => UserId::fromInt($contact->user_id),
            'owner' => AccountOwner::contact(
                ContactId::fromInt($contact->id),
            ),
        ];
    }

    private function authorizeOwner(BankAccount $entry): void
    {
        $permission = $entry->business_id !== null ? Permission::BUSINESS_UPDATE : Permission::CONTACT_UPDATE;
        $user = backpack_user();
        abort_unless(
            $user?->can($permission) === true,
            403,
        );
    }

    private function userOf(BankAccount $entry): UserId
    {
        $owner = $entry->business ?? $entry->contact;
        abort_if($owner === null, 404);

        return UserId::fromInt($owner->user_id);
    }

    private function ownerOf(BankAccount $entry): AccountOwner
    {
        if ($entry->business_id !== null) {
            return AccountOwner::business(
                BusinessId::fromInt($entry->business_id),
            );
        }

        return AccountOwner::contact(
            ContactId::fromInt($entry->contact_id),
        );
    }

    private function isDefault(BankAccount $entry): bool
    {
        $owner = $entry->business ?? $entry->contact;

        return $owner !== null && $owner->default_bank_account_id === $entry->id;
    }

    private function validatedRequest(): BankAccountRequest
    {
        $request = $this->crud->validateRequest();
        abort_unless($request instanceof BankAccountRequest, 500);

        return $request;
    }

    private function entry(): BankAccount
    {
        $entry = $this->crud->getCurrentEntry();
        abort_unless($entry instanceof BankAccount, 404);

        return $entry;
    }
}
