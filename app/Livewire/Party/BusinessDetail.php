<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Commands\ArchiveBusiness;
use App\Application\Party\Commands\RestoreBusiness;
use App\Application\Party\Commands\SetDefaultBusiness;
use App\Application\Party\Queries\FindDefaultBusiness;
use App\Application\Party\Queries\GetBusiness;
use App\Application\Party\Queries\ListActiveBusinesses;
use App\Domain\Party\Exceptions\BusinessArchivedException;
use App\Domain\Party\Exceptions\DefaultBusinessRequiredException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Livewire\Shared\ActsForUser;
use App\Livewire\Shared\ThrottlesCreation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The detail of one of the user's businesses with archiving, restoring and the default selection. Archiving the
 * default business while others stay active asks which of them becomes the default. The page has found the business
 * before mounting it.
 */
final class BusinessDetail extends Component
{
    use ActsForUser;
    use ProvidesPartyOptions;
    use ThrottlesCreation;

    #[Locked]
    public int $businessId;

    public bool $choosingNewDefault = false;

    public string $newDefault = '';

    public ?string $errorMessage = null;

    public ?string $limitMessage = null;

    public ?string $successMessage = null;

    public function mount(int $businessId): void
    {
        $this->requireAllowed(GetBusiness::class);
        $this->businessId = $businessId;
    }

    public function archive(): void
    {
        $this->requireAllowed(ArchiveBusiness::class);
        $this->errorMessage = null;
        $this->successMessage = null;
        $newDefault = $this->newDefault === '' ? null : BusinessId::fromInt((int) $this->newDefault);

        try {
            $this->runUseCase(
                new ArchiveBusiness(
                    $this->userId(),
                    $this->currentId(),
                    $newDefault,
                ),
            );
        } catch (DefaultBusinessRequiredException) {
            $this->choosingNewDefault = true;

            return;
        } catch (BusinessArchivedException) {
            $this->errorMessage = __('businesses.messages.new_default_archived');

            return;
        }

        $this->choosingNewDefault = false;
        $this->newDefault = '';
        $this->successMessage = __('businesses.messages.archived');
    }

    public function cancelArchive(): void
    {
        $this->choosingNewDefault = false;
        $this->newDefault = '';
    }

    public function restore(): void
    {
        $this->requireAllowed(RestoreBusiness::class);
        $this->limitMessage = null;
        $this->successMessage = null;

        $this->errorMessage = $this->creationThrottled();
        if ($this->errorMessage !== null) {
            return;
        }

        try {
            $this->runUseCase(
                new RestoreBusiness(
                    $this->userId(),
                    $this->currentId(),
                ),
            );
        } catch (EntityLimitExceededException $e) {
            $this->limitMessage = __('businesses.messages.limit_exceeded', ['limit' => $e->limit]);

            return;
        }

        $this->successMessage = __('businesses.messages.restored');
    }

    public function makeDefault(): void
    {
        $this->requireAllowed(SetDefaultBusiness::class);
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $this->runUseCase(
                new SetDefaultBusiness(
                    $this->userId(),
                    $this->currentId(),
                ),
            );
        } catch (BusinessArchivedException) {
            $this->errorMessage = __('businesses.messages.default_archived');

            return;
        }

        $this->successMessage = __('businesses.messages.default_changed');
    }

    public function render(): View
    {
        $owner = $this->userId();
        $business = $this->runUseCase(
            new GetBusiness($owner, $this->currentId()),
        );
        $defaultId = $this->runUseCase(
            new FindDefaultBusiness($owner),
        );
        $active = $this->runUseCase(
            new ListActiveBusinesses($owner),
        );
        $newDefaultOptions = [];
        foreach ($active as $other) {
            if (!$other->id->equals($business->id)) {
                $newDefaultOptions[$other->id->value] = $other->profile->name;
            }
        }

        return view(
            'livewire.party.business-detail',
            [
                'business' => $business,
                'isDefault' => $defaultId !== null && $defaultId->equals($business->id),
                'newDefaultOptions' => $newDefaultOptions,
                'countries' => $this->countryOptions(),
            ],
        );
    }

    private function currentId(): BusinessId
    {
        return BusinessId::fromInt($this->businessId);
    }
}
