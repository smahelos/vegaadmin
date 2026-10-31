<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Party\Services\CompanyRegistryApplicationService;

/**
 * Behaviour every form with party details shares: the ARES prefill and the registration country that follows the
 * address.
 *
 * The component declares the public property `PartyDetailsForm $party`.
 */
trait EditsPartyDetails
{
    public ?string $registerMessage = null;

    public function updatedPartyCompanyNumber(): void
    {
        $this->party->prefillRegistrationCountry();
    }

    /**
     * Prefills the form from ARES for the user to check; nothing is saved.
     */
    public function lookUpCompany(CompanyRegistryApplicationService $registry): void
    {
        $this->registerMessage = $this->party->lookUpInRegister($registry) ? __('parties.messages.register_filled') : null;
    }
}
