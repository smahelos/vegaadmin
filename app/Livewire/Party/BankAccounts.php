<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\BankAccountApplicationService;
use App\Domain\Party\Exceptions\BankAccountArchivedException;
use App\Domain\Party\Exceptions\DefaultBankAccountRequiredException;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Livewire\Party\Forms\BankAccountForm;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The bank accounts of a business or a contact, written once for both owners. Changing them falls under the
 * owner's update permission. Archiving the default account while others stay active asks for the new default.
 */
final class BankAccounts extends Component
{
    use ActsForUser;
    use ProvidesPartyOptions;

    public const OWNER_BUSINESS = 'business';

    public const OWNER_CONTACT = 'contact';

    #[Locked]
    public string $ownerType;

    #[Locked]
    public int $ownerId;

    public BankAccountForm $account;

    public bool $editing = false;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $archivingId = null;

    public string $newDefault = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function mount(
        BankAccountApplicationService $accounts,
        string $ownerType,
        int $ownerId,
    ): void {
        abort_unless(in_array($ownerType, [self::OWNER_BUSINESS, self::OWNER_CONTACT], true), 404);
        $this->ownerType = $ownerType;
        $this->ownerId = $ownerId;
        $this->requirePermission($this->permission('view'));
        $accounts->listForOwner($this->userId(), $this->owner());
    }

    public function add(): void
    {
        $this->requirePermission($this->permission('update'));
        $this->account->reset();
        $this->account->resetErrorBag();
        $this->editingId = null;
        $this->editing = true;
    }

    public function edit(BankAccountApplicationService $accounts, int $id): void
    {
        $this->requirePermission($this->permission('update'));
        $this->account->resetErrorBag();
        $this->account->fillFromAccount($accounts->getForOwner($this->userId(), $this->owner(), BankAccountId::fromInt($id)));
        $this->editingId = $id;
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->editingId = null;
        $this->archivingId = null;
        $this->newDefault = '';
        $this->account->reset();
    }

    public function save(BankAccountApplicationService $accounts): void
    {
        $this->requirePermission($this->permission('update'));
        $this->account->validate();

        if ($this->editingId === null) {
            $accounts->add(
                $this->userId(),
                $this->owner(),
                $this->account->writeData(),
            );
        } else {
            $accounts->update(
                $this->userId(),
                $this->owner(),
                BankAccountId::fromInt($this->editingId),
                $this->account->writeData(),
            );
        }

        $this->cancel();
        $this->successMessage = __('parties.bank_accounts.messages.saved');
    }

    public function archive(BankAccountApplicationService $accounts, int $id): void
    {
        $this->requirePermission($this->permission('update'));
        $this->errorMessage = null;
        $this->successMessage = null;
        $newDefault = $this->archivingId === $id && $this->newDefault !== ''
            ? BankAccountId::fromInt((int) $this->newDefault)
            : null;

        try {
            $accounts->archive(
                $this->userId(),
                $this->owner(),
                BankAccountId::fromInt($id),
                $newDefault,
            );
        } catch (DefaultBankAccountRequiredException) {
            $this->archivingId = $id;

            return;
        } catch (BankAccountArchivedException) {
            $this->errorMessage = __('parties.bank_accounts.messages.default_archived');

            return;
        }

        $this->cancel();
        $this->successMessage = __('parties.bank_accounts.messages.archived');
    }

    public function restore(BankAccountApplicationService $accounts, int $id): void
    {
        $this->requirePermission($this->permission('update'));
        $accounts->restore(
            $this->userId(),
            $this->owner(),
            BankAccountId::fromInt($id),
        );
        $this->successMessage = __('parties.bank_accounts.messages.restored');
    }

    public function makeDefault(BankAccountApplicationService $accounts, int $id): void
    {
        $this->requirePermission($this->permission('update'));
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $accounts->setDefault(
                $this->userId(),
                $this->owner(),
                BankAccountId::fromInt($id),
            );
        } catch (BankAccountArchivedException) {
            $this->errorMessage = __('parties.bank_accounts.messages.default_archived');

            return;
        }

        $this->successMessage = __('parties.bank_accounts.messages.default_changed');
    }

    public function render(BankAccountApplicationService $accounts): View
    {
        $userId = $this->userId();
        $ownerAccounts = $accounts->listForOwner($userId, $this->owner());
        $newDefaultOptions = [];
        foreach ($ownerAccounts as $candidate) {
            if (! $candidate->isArchived() && $candidate->id->value !== $this->archivingId) {
                $newDefaultOptions[$candidate->id->value] = $candidate->label ?? $candidate->account->formattedIban();
            }
        }

        return view('livewire.party.bank-accounts', [
            'accounts' => $ownerAccounts,
            'newDefaultOptions' => $newDefaultOptions,
            'defaultId' => $accounts->findDefaultId($userId, $this->owner()),
            'canUpdate' => $this->userCan($this->permission('update')),
            'bankOptions' => $this->bankOptions($this->account->country),
            'countries' => $this->countryOptions(),
        ]);
    }

    private function owner(): AccountOwner
    {
        return $this->ownerType === self::OWNER_BUSINESS
            ? AccountOwner::business(BusinessId::fromInt($this->ownerId))
            : AccountOwner::contact(ContactId::fromInt($this->ownerId));
    }

    private function permission(string $action): string
    {
        $business = $this->ownerType === self::OWNER_BUSINESS;

        return match ($action) {
            'view' => $business ? 'frontend.can_view_business' : 'frontend.can_view_contact',
            'update' => $business ? 'frontend.can_update_business' : 'frontend.can_update_contact',
        };
    }
}
