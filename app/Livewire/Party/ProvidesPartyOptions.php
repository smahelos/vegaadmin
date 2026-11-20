<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Application\Payment\Services\BankApplicationService;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Domain\Shared\Geography\Enums\Country;
use Illuminate\Support\Facades\App;

/**
 * Options of the country and bank selects of party and bank account forms.
 */
trait ProvidesPartyOptions
{
    private CountryOptionsPresenter $countries;

    private BankApplicationService $banks;

    /** Livewire calls it through the container before every request of the component. */
    public function bootProvidesPartyOptions(CountryOptionsPresenter $countries, BankApplicationService $banks): void
    {
        $this->countries = $countries;
        $this->banks = $banks;
    }

    /**
     * @return array<string, string> country name by code
     */
    protected function countryOptions(): array
    {
        return $this->countries->options(App::getLocale());
    }

    /**
     * Active banks of the account's country; empty for a country without a bank list, where the code is typed.
     *
     * @return array<string, string> label by bank code
     */
    protected function bankOptions(string $country): array
    {
        $country = Country::tryFrom($country);
        if ($country === null) {
            return [];
        }

        $options = [];
        foreach ($this->banks->listActive($country) as $bank) {
            if ($bank->code !== null) {
                $options[$bank->code] = $bank->code . ' – ' . $bank->name;
            }
        }

        return $options;
    }
}
