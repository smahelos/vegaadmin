<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\BusinessApplicationService;
use App\Application\Party\Services\PartyLogoApplicationService;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Livewire\Party\Forms\BankAccountForm;
use App\Livewire\Party\Forms\PartyDetailsForm;
use App\Livewire\Shared\ActsForUser;
use App\Livewire\Shared\ThrottlesCreation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Creates or edits one of the user's own businesses. A new business may get its first bank account right away,
 * which becomes its default; further accounts are managed on the business detail.
 */
final class BusinessForm extends Component
{
    use ActsForUser;
    use ChecksPartyDuplicates;
    use EditsPartyDetails;
    use EditsPartyLogo;
    use ProvidesPartyOptions;
    use ThrottlesCreation;

    public PartyDetailsForm $party;

    public BankAccountForm $account;

    #[Locked]
    public ?int $businessId = null;

    public ?string $errorMessage = null;

    public ?string $limitMessage = null;

    public function mount(BusinessApplicationService $businesses, ?int $businessId = null): void
    {
        if ($businessId === null) {
            $this->requirePermission('frontend.can_create_business');
            $this->party->country = 'CZ';

            return;
        }

        $this->requirePermission('frontend.can_update_business');
        $business = $businesses->get(
            $this->userId(),
            BusinessId::fromInt($businessId),
        );

        $this->businessId = $businessId;
        $this->party->fillFromProfile($business->profile);
        $this->currentLogo = $business->logo;
    }

    public function save(BusinessApplicationService $businesses, PartyLogoApplicationService $logos): void
    {
        $creating = $this->businessId === null;
        $this->requirePermission(
            $creating ? 'frontend.can_create_business' : 'frontend.can_update_business',
        );
        $this->errorMessage = null;
        $this->limitMessage = null;

        $this->validate($this->rules());
        $this->party->validate();
        $withAccount = $creating && $this->account->isFilled();
        if ($withAccount) {
            $this->account->validate();
        }

        $profile = $this->party->profile();
        if (!$this->passesDuplicateCheck($profile)) {
            return;
        }

        if ($creating) {
            $this->errorMessage = $this->creationThrottled();
            if ($this->errorMessage !== null) {
                return;
            }
        }

        $logo = $this->logoPath($logos);
        $data = new BusinessWriteData($profile, $logo);

        $saved = false;
        try {
            if ($creating) {
                $business = $businesses->create(
                    $this->userId(),
                    $data,
                    $withAccount ? $this->account->writeData() : null,
                );
            } else {
                $business = $businesses->update(
                    $this->userId(),
                    BusinessId::fromInt($this->businessId),
                    $data,
                );
            }
            $saved = true;
        } catch (EntityLimitExceededException $e) {
            $this->limitMessage = __('businesses.messages.limit_exceeded', ['limit' => $e->limit]);

            return;
        } catch (DuplicateCompanyNumberException $e) {
            $this->showBlockingDuplicate($e->existing);

            return;
        } finally {
            if (!$saved) {
                $this->discardNewLogo($logos, $logo);
            }
        }

        if ($this->currentLogo !== null && $this->currentLogo !== $logo) {
            $logos->delete($this->currentLogo);
        }

        $session = session();
        $session->flash(
            'success',
            __(
                $creating ? 'businesses.messages.created' : 'businesses.messages.updated',
            ),
        );
        $application = app();
        $this->redirectRoute(
            'frontend.businesses.show',
            [
                'locale' => $application->getLocale(),
                'business' => $business->id->value,
            ],
        );
    }

    public function render(): View
    {
        return view(
            'livewire.party.business-form',
            [
                'countries' => $this->countryOptions(),
                'cancelHref' => $this->cancelHref(),
                'bankOptions' => $this->bankOptions($this->account->country),
            ],
        );
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['logo' => __('parties.fields.logo')];
    }

    protected function findDuplicates(PartyProfile $profile): array
    {
        $businesses = app(BusinessApplicationService::class);

        return $businesses->findDuplicates(
            $this->userId(),
            $profile,
            $this->businessId === null ? null : BusinessId::fromInt($this->businessId),
        );
    }

    protected function duplicateUrl(BusinessId|ContactId $id): string
    {
        $application = app();

        return route(
            'frontend.businesses.show',
            [
                'locale' => $application->getLocale(),
                'business' => $id->value,
            ],
        );
    }

    private function cancelHref(): string
    {
        $application = app();
        $locale = $application->getLocale();

        if ($this->businessId === null) {
            return route('frontend.businesses.index', ['locale' => $locale]);
        }

        return route('frontend.businesses.show', ['locale' => $locale, 'business' => $this->businessId]);
    }
}
