<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\BusinessApplicationService;
use App\Domain\Party\Exceptions\BusinessArchivedException;
use App\Domain\Party\Exceptions\DefaultBusinessRequiredException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Livewire\Shared\ActsForUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The detail of one of the user's businesses with archiving, restoring and the default selection. Archiving the
 * default business while others stay active asks which of them becomes the default.
 */
final class BusinessDetail extends Component
{
    use ActsForUser;
    use ProvidesPartyOptions;

    #[Locked]
    public int $businessId;

    public bool $choosingNewDefault = false;

    public string $newDefault = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public function mount(BusinessApplicationService $businesses, int $businessId): void
    {
        $this->requirePermission('frontend.can_view_business');
        $businesses->get($this->userId(), BusinessId::fromInt($businessId));
        $this->businessId = $businessId;
    }

    public function archive(BusinessApplicationService $businesses): void
    {
        $this->requirePermission('frontend.can_archive_business');
        $this->errorMessage = null;
        $this->successMessage = null;
        $newDefault = $this->newDefault === '' ? null : BusinessId::fromInt((int) $this->newDefault);

        try {
            $businesses->archive(
                $this->userId(),
                $this->currentId(),
                $newDefault,
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

    public function restore(BusinessApplicationService $businesses): void
    {
        $this->requirePermission('frontend.can_restore_business');
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $businesses->restore($this->userId(), $this->currentId());
        } catch (EntityLimitExceededException) {
            $this->errorMessage = __('businesses.messages.limit_exceeded');

            return;
        }

        $this->successMessage = __('businesses.messages.restored');
    }

    public function makeDefault(BusinessApplicationService $businesses): void
    {
        $this->requirePermission('frontend.can_set_default_business');
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $businesses->setDefault($this->userId(), $this->currentId());
        } catch (BusinessArchivedException) {
            $this->errorMessage = __('businesses.messages.default_archived');

            return;
        }

        $this->successMessage = __('businesses.messages.default_changed');
    }

    public function render(BusinessApplicationService $businesses): View
    {
        $userId = $this->userId();
        $business = $businesses->get($userId, $this->currentId());
        $defaultId = $businesses->findDefaultId($userId);
        $newDefaultOptions = [];
        foreach ($businesses->listActive($userId) as $other) {
            if (! $other->id->equals($business->id)) {
                $newDefaultOptions[$other->id->value] = $other->profile->name;
            }
        }

        return view('livewire.party.business-detail', [
            'business' => $business,
            'isDefault' => $defaultId !== null && $defaultId->equals($business->id),
            'newDefaultOptions' => $newDefaultOptions,
            'countries' => $this->countryOptions(),
        ]);
    }

    private function currentId(): BusinessId
    {
        return BusinessId::fromInt($this->businessId);
    }
}
