<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Commands\ArchiveBankAccount;
use App\Application\Party\Commands\CreateBankAccount;
use App\Application\Party\Commands\RestoreBankAccount;
use App\Application\Party\Commands\SetDefaultBankAccount;
use App\Application\Party\Commands\UpdateBankAccount;
use App\Application\Party\Queries\FindDefaultBankAccount;
use App\Application\Party\Queries\GetBankAccountForEdit;
use App\Application\Party\Queries\ListBankAccounts;
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

    public function mount(string $ownerType, int $ownerId): void
    {
        abort_unless(
            in_array(
                $ownerType,
                [self::OWNER_BUSINESS, self::OWNER_CONTACT],
                true,
            ),
            404,
        );
        $this->ownerType = $ownerType;
        $this->ownerId = $ownerId;
        $this->requireAllowed(ListBankAccounts::class);
    }

    public function add(): void
    {
        $this->requireAllowed(CreateBankAccount::class);
        $this->account->reset();
        $this->account->resetErrorBag();
        $this->editingId = null;
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $this->requireAllowed(GetBankAccountForEdit::class);
        $this->account->resetErrorBag();
        $this->account->fillFromAccount(
            $this->runUseCase(
                new GetBankAccountForEdit(
                    $this->userId(),
                    $this->owner(),
                    BankAccountId::fromInt($id),
                ),
            ),
        );
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

    public function save(): void
    {
        $this->requireAllowed(
            $this->editingId === null ? CreateBankAccount::class : UpdateBankAccount::class,
        );
        $this->account->validate();

        if ($this->editingId === null) {
            $this->runUseCase(
                new CreateBankAccount(
                    $this->userId(),
                    $this->owner(),
                    $this->account->writeData(),
                ),
            );
        } else {
            $this->runUseCase(
                new UpdateBankAccount(
                    $this->userId(),
                    $this->owner(),
                    BankAccountId::fromInt($this->editingId),
                    $this->account->writeData(),
                ),
            );
        }

        $this->cancel();
        $this->successMessage = __('parties.bank_accounts.messages.saved');
    }

    public function archive(int $id): void
    {
        $this->requireAllowed(ArchiveBankAccount::class);
        $this->errorMessage = null;
        $this->successMessage = null;
        $newDefault = $this->archivingId === $id && $this->newDefault !== '' ? BankAccountId::fromInt((int) $this->newDefault) : null;

        try {
            $this->runUseCase(
                new ArchiveBankAccount(
                    $this->userId(),
                    $this->owner(),
                    BankAccountId::fromInt($id),
                    $newDefault,
                ),
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

    public function restore(int $id): void
    {
        $this->requireAllowed(RestoreBankAccount::class);
        $this->runUseCase(
            new RestoreBankAccount(
                $this->userId(),
                $this->owner(),
                BankAccountId::fromInt($id),
            ),
        );
        $this->successMessage = __('parties.bank_accounts.messages.restored');
    }

    public function makeDefault(int $id): void
    {
        $this->requireAllowed(SetDefaultBankAccount::class);
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $this->runUseCase(
                new SetDefaultBankAccount(
                    $this->userId(),
                    $this->owner(),
                    BankAccountId::fromInt($id),
                ),
            );
        } catch (BankAccountArchivedException) {
            $this->errorMessage = __('parties.bank_accounts.messages.default_archived');

            return;
        }

        $this->successMessage = __('parties.bank_accounts.messages.default_changed');
    }

    public function render(): View
    {
        $owner = $this->userId();
        $ownerAccounts = $this->runUseCase(
            new ListBankAccounts($owner, $this->owner()),
        );
        $newDefaultOptions = [];
        foreach ($ownerAccounts as $candidate) {
            if (!$candidate->isArchived() && $candidate->id->value !== $this->archivingId) {
                $newDefaultOptions[$candidate->id->value] = $candidate->label ?? $candidate->account->formattedIban();
            }
        }

        return view(
            'livewire.party.bank-accounts',
            [
                'accounts' => $ownerAccounts,
                'newDefaultOptions' => $newDefaultOptions,
                'defaultId' => $this->runUseCase(
                    new FindDefaultBankAccount($owner, $this->owner()),
                ),
                'bankOptions' => $this->bankOptions($this->account->country),
                'countries' => $this->countryOptions(),
            ],
        );
    }

    private function owner(): AccountOwner
    {
        if ($this->ownerType === self::OWNER_BUSINESS) {
            return AccountOwner::business(
                BusinessId::fromInt($this->ownerId),
            );
        }

        return AccountOwner::contact(
            ContactId::fromInt($this->ownerId),
        );
    }
}
