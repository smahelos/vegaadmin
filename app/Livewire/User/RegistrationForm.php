<?php

declare(strict_types=1);

namespace App\Livewire\User;

use App\Application\User\Services\RegistrationApplicationService;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\User\DTO\UserWriteData;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Livewire\Party\EditsPartyDetails;
use App\Livewire\Party\Forms\BankAccountForm;
use App\Livewire\Party\Forms\PartyDetailsForm;
use App\Livewire\Party\ProvidesPartyOptions;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Registers a frontend account together with its first business. The business name and email start as a copy of
 * the login details while the user has not typed their own, and stay independent afterwards. A bank account is
 * optional; when filled it becomes the business's default.
 */
final class RegistrationForm extends Component
{
    use EditsPartyDetails;
    use ProvidesPartyOptions;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public PartyDetailsForm $party;

    public BankAccountForm $account;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->party->country = 'CZ';
    }

    public function updatingName(string $value): void
    {
        if ($this->party->name === $this->name) {
            $this->party->name = $value;
        }
    }

    public function updatingEmail(string $value): void
    {
        if ($this->party->email === $this->email) {
            $this->party->email = $value;
        }
    }

    public function save(RegistrationApplicationService $registration): void
    {
        $this->errorMessage = null;
        $this->validate($this->rules());
        $this->party->validate();
        $withAccount = $this->account->isFilled();
        if ($withAccount) {
            $this->account->validate();
        }

        try {
            $user = $registration->register(
                new UserWriteData(
                    name: trim($this->name),
                    email: trim($this->email),
                    password: $this->password,
                ),
                new BusinessWriteData($this->party->profile(), null),
                $withAccount ? $this->account->writeData() : null,
            );
        } catch (EntityLimitExceededException $e) {
            // The registration plan must allow its first business; reaching this is a configuration error
            Log::error('Registration plan allows no business', ['exception' => $e]);
            $this->errorMessage = __('users.auth.registration_failed');

            return;
        }

        $account = Auth::loginUsingId($user->id);
        if ($account !== false) {
            event(new Registered($account));
            session()->regenerate();
        }

        session()->flash('success', __('users.auth.registration_success'));
        $this->redirectRoute('home', ['locale' => app()->getLocale()]);
    }

    public function render(): View
    {
        return view('livewire.user.registration-form', [
            'countries' => $this->countryOptions(),
            'bankOptions' => $this->bankOptions($this->account->country),
        ]);
    }

    /**
     * @return array<string, list<string|object>>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'same:passwordConfirmation',
                Password::min(8),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => __('users.fields.name'),
            'email' => __('users.fields.email'),
            'password' => __('users.fields.password'),
            'passwordConfirmation' => __('users.fields.password_confirmation'),
        ];
    }
}
