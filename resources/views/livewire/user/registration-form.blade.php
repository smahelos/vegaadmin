<div>
    <x-status-messages :error="$errorMessage" />

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                <x-party.details-fields :countries="$countries" :register-message="$registerMessage" />
            </x-form.card>
        </div>

        <div class="flex justify-between mt-6 mb-20 md:mb-10">
            <a href="{{ route('frontend.login', ['locale' => app()->getLocale()]) }}"
                class="inline-flex justify-center py-6 px-12 border border-white dark:border-gray-700 shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 dark:shadow-md text-lg font-semibold rounded-sm text-gray-700 dark:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                {{ __('users.messages.login_prompt') }}
            </a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="inline-flex items-center px-12 py-6 border border-transparent rounded-sm dark:shadow-md shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 text-lg font-semibold text-white bg-[#490BF4] hover:bg-indigo-500 cursor-pointer transition-colors duration-200">
                <i class="fas fa-user-plus mr-2"></i>
                {{ __('users.actions.register') }}
            </button>
        </div>
    </form>
</div>
