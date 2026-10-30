<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Party\Services\BankAccountApplicationService;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
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
    use UpdateOperation;

    public function setup(): void
    {
        CRUD::setModel(BankAccount::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/bank-account');
        CRUD::setEntityNameStrings(
            __('parties.bank_accounts.entity.singular'),
            __('parties.bank_accounts.entity.plural'),
        );

        $user = backpack_user();
        $denied = [];
        if ($user?->can('can_view_business') !== true && $user?->can('can_view_contact') !== true) {
            $denied[] = 'list';
        }

        if ($user?->can('can_update_business') !== true && $user?->can('can_update_contact') !== true) {
            $denied[] = 'create';
            $denied[] = 'update';
        }

        CRUD::denyAccess($denied);
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

        Alert::success(trans('backpack::crud.insert_success'))->flash();
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

        Alert::success(trans('backpack::crud.update_success'))->flash();
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($entry->id);
    }

    public function archive(BankAccountApplicationService $accounts, int $id): RedirectResponse
    {
        $entry = BankAccount::query()->findOrFail($id);
        $this->authorizeOwner($entry);
        $accounts->archive(
            $this->userOf($entry),
            $this->ownerOf($entry),
            BankAccountId::fromInt($entry->id),
            null,
        );
        Alert::success(__('parties.bank_accounts.messages.archived'))->flash();

        return redirect()->back();
    }

    public function restore(BankAccountApplicationService $accounts, int $id): RedirectResponse
    {
        $entry = BankAccount::query()->findOrFail($id);
        $this->authorizeOwner($entry);
        $accounts->restore(
            $this->userOf($entry),
            $this->ownerOf($entry),
            BankAccountId::fromInt($entry->id),
        );
        Alert::success(__('parties.bank_accounts.messages.restored'))->flash();

        return redirect()->back();
    }

    public function setDefault(BankAccountApplicationService $accounts, int $id): RedirectResponse
    {
        $entry = BankAccount::query()->findOrFail($id);
        $this->authorizeOwner($entry);
        $accounts->setDefault(
            $this->userOf($entry),
            $this->ownerOf($entry),
            BankAccountId::fromInt($entry->id),
        );
        Alert::success(__('parties.bank_accounts.messages.default_changed'))->flash();

        return redirect()->back();
    }

    protected function setupListOperation(): void
    {
        $businessId = request()->integer('business_id');
        $contactId = request()->integer('contact_id');
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

        CRUD::column('owner')
            ->label(__('parties.bank_accounts.fields.owner'))
            ->type('closure')
            ->function(fn (BankAccount $entry): string => e($entry->business->name ?? $entry->contact->name ?? ''))
            ->escaped(false);
        CRUD::column('label')->label(__('parties.bank_accounts.fields.label'));
        CRUD::column('iban')->label(__('parties.bank_accounts.fields.iban'));
        CRUD::column('number')->label(__('parties.bank_accounts.fields.number'));
        CRUD::column('bank_code')->label(__('parties.bank_accounts.fields.bank_code'));
        CRUD::column('default')
            ->label(__('parties.bank_accounts.fields.default'))
            ->type('closure')
            ->function(fn (BankAccount $entry): string => $this->isDefault($entry) ? e(__('general.yes')) : '')
            ->escaped(false);
        CRUD::column('archived_at')
            ->label(__('parties.statuses.archived'))
            ->type('datetime');

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
        CRUD::field('business_id')
            ->type('hidden')
            ->value(request()->integer('business_id') ?: null);
        CRUD::field('contact_id')
            ->type('hidden')
            ->value(request()->integer('contact_id') ?: null);
        $this->addAccountFields();
    }

    protected function setupUpdateOperation(): void
    {
        CRUD::setValidation(BankAccountRequest::class);
        $this->addAccountFields();
    }

    private function addAccountFields(): void
    {
        CRUD::addFields(PartyFormFields::bankAccount(app(CountryOptionsPresenter::class)->options(app()->getLocale())));
    }

    /**
     * @return array{user: UserId, owner: AccountOwner}
     */
    private function ownerFromRequest(BankAccountRequest $request): array
    {
        $businessId = $request->integer('business_id');
        if ($businessId > 0) {
            $business = Business::query()->findOrFail($businessId);

            return [
                'user' => UserId::fromInt($business->user_id),
                'owner' => AccountOwner::business(BusinessId::fromInt($business->id)),
            ];
        }

        $contact = Contact::query()->findOrFail($request->integer('contact_id'));

        return [
            'user' => UserId::fromInt($contact->user_id),
            'owner' => AccountOwner::contact(ContactId::fromInt($contact->id)),
        ];
    }

    private function authorizeOwner(BankAccount $entry): void
    {
        $permission = $entry->business_id !== null ? 'can_update_business' : 'can_update_contact';
        abort_unless(backpack_user()?->can($permission) === true, 403);
    }

    private function userOf(BankAccount $entry): UserId
    {
        $owner = $entry->business ?? $entry->contact;
        abort_if($owner === null, 404);

        return UserId::fromInt($owner->user_id);
    }

    private function ownerOf(BankAccount $entry): AccountOwner
    {
        return $entry->business_id !== null
            ? AccountOwner::business(BusinessId::fromInt($entry->business_id))
            : AccountOwner::contact(ContactId::fromInt($entry->contact_id));
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
