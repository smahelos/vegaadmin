<?php

declare(strict_types=1);

namespace App\Livewire\Party\Forms;

use App\Application\Party\Services\CompanyRegistryApplicationService;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\DTO\RegisteredCompanyDTO;
use App\Domain\Party\Exceptions\CompanyRegistryUnavailableException;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Shared\Geography\Enums\Country;
use App\Http\Requests\Party\PartyInput;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Form;

/**
 * The profile fields of a business, a contact or a document party, written once for every form that edits one.
 */
final class PartyDetailsForm extends Form
{
    public string $name = '';

    public string $shortName = '';

    public string $registrationCountry = '';

    public string $companyNumber = '';

    public string $vatNumber = '';

    public string $email = '';

    public string $phone = '';

    public string $note = '';

    public string $street = '';

    public string $city = '';

    public string $postalCode = '';

    public string $country = '';

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return PartyInput::rules($this->input(), Str::camel(...));
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return PartyInput::attributes(Str::camel(...));
    }

    /**
     * Call after validate(): the validated fields as the profile.
     */
    public function profile(): PartyProfile
    {
        return PartyInput::profile($this->input());
    }

    public function fillFromProfile(PartyProfile $profile): void
    {
        $this->fillInput(PartyInput::fromProfile($profile));
    }

    /**
     * The registration country follows the address country while it is empty; once chosen, a later change of the
     * address leaves it alone.
     */
    public function prefillRegistrationCountry(): void
    {
        if ($this->registrationCountry === '' && trim($this->companyNumber) !== '') {
            $this->registrationCountry = $this->country;
        }
    }

    /**
     * Fills the form from the register for a Czech company number; false with an error on the company number when
     * the number is not Czech, unknown or the register did not answer.
     */
    public function lookUpInRegister(CompanyRegistryApplicationService $registry): bool
    {
        $this->prefillRegistrationCountry();
        if ($this->registrationCountry === '') {
            $this->registrationCountry = Country::CZ->value;
        }

        $this->validateOnly('companyNumber');
        $number = RegistrationNumber::fromParts(
            Country::tryFrom($this->registrationCountry),
            $this->companyNumber,
        );
        if ($number === null || ! $number->isCzech()) {
            $this->addError('companyNumber', __('parties.messages.register_czech_only'));

            return false;
        }

        try {
            $company = $registry->findCompany($number);
        } catch (CompanyRegistryUnavailableException $e) {
            Log::warning('Company register lookup failed', ['exception' => $e]);
            $this->addError('companyNumber', __('parties.messages.register_unavailable'));

            return false;
        }

        if ($company === null) {
            $this->addError('companyNumber', __('parties.messages.register_not_found'));

            return false;
        }

        $this->fillFromRegister($company);

        return true;
    }

    /**
     * Overwrites the name, VAT number and address with the register's data for the user to check; the VAT number
     * stays as typed when the register has none.
     */
    private function fillFromRegister(RegisteredCompanyDTO $company): void
    {
        $this->name = $company->name;
        if ($company->vatNumber !== null) {
            $this->vatNumber = $company->vatNumber->value;
        }

        if ($company->address !== null) {
            $this->street = $company->address->street ?? '';
            $this->city = $company->address->city ?? '';
            $this->postalCode = $company->address->postalCode ?? '';
            $this->country = $company->address->country->value;
        }
    }

    /**
     * @return array<string, string>
     */
    public function input(): array
    {
        $input = [];
        foreach (PartyInput::FIELDS as $field) {
            $input[$field] = $this->{Str::camel($field)};
        }

        return $input;
    }

    /**
     * @param  array<string, string>  $input
     */
    private function fillInput(array $input): void
    {
        foreach (PartyInput::FIELDS as $field) {
            $this->{Str::camel($field)} = $input[$field] ?? '';
        }
    }
}
