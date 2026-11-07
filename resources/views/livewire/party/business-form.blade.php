<div>
    <x-status-messages :error="$errorMessage" />
    <x-limit-exceeded :message="$limitMessage" />

    <x-party.duplicates :blocking="$blockingDuplicate" :warnings="$duplicateWarnings" />

    <form wire:submit="save">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-form.card :title="__('parties.sections.basic')">
                <x-party.basic-fields />

                <x-party.logo-fields :logo="$logo" :current-logo="$currentLogo" :remove-logo="$removeLogo" />
            </x-form.card>

            <x-form.card :title="__('parties.sections.billing')">
                <x-party.billing-fields :countries="$countries" :register-message="$registerMessage" />

                @if ($businessId === null)
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white pt-7 mb-2">{{ __('businesses.sections.first_bank_account') }}</h3>
                    <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">{{ __('businesses.hints.first_bank_account') }}</p>
                    <x-party.bank-account-fields :bank-options="$bankOptions" :countries="$countries" />
                @endif
            </x-form.card>
        </div>

        <x-form.actions
            :cancel-href="$cancelHref"
            :cancel-label="__('general.actions.cancel')"
            :submit-label="__('general.actions.save')" />
    </form>
</div>
