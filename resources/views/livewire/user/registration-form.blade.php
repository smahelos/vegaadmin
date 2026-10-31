<div>
    <x-status-messages :error="$errorMessage" />

    <form wire:submit="save">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-6">
                <x-form.card :title="__('users.sections.basic_info')">
                    <div class="space-y-5">
                        <x-form.input model="name" :label="__('users.fields.name')" required autocomplete="name" />
                        <x-form.input type="email" model="email" :label="__('users.fields.email')" required autocomplete="email" />
                        <x-form.input type="password" model="password" :label="__('users.fields.password')" required autocomplete="new-password" />
                        <x-form.input type="password" model="passwordConfirmation" :label="__('users.fields.password_confirmation')" required
                            autocomplete="new-password" />
                    </div>
                </x-form.card>

                <x-form.card :title="__('businesses.sections.first_bank_account')">
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ __('businesses.hints.first_bank_account') }}</p>
                    <x-party.bank-account-fields :bank-options="$bankOptions" :countries="$countries" />
                </x-form.card>
            </div>

            <x-form.card :title="__('users.sections.business')">
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ __('users.hints.business') }}</p>
                <x-party.basic-fields />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white pt-7 mb-5">{{ __('parties.sections.billing') }}</h3>
                <x-party.billing-fields :countries="$countries" :register-message="$registerMessage" />
            </x-form.card>
        </div>

        <x-form.actions :cancel-href="route('frontend.login', ['locale' => app()->getLocale()])"
            :cancel-label="__('users.messages.login_prompt')"
            :submit-label="__('users.actions.register')"
            submit-icon="fa-user-plus" />
    </form>
</div>
