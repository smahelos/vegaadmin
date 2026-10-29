<div>
    @if ($limitMessage)
        <div class="mb-6 rounded-sm border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-200">
            {{ $limitMessage }}
        </div>
    @endif

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
            :cancel-href="$businessId === null ? route('frontend.businesses.index', ['locale' => app()->getLocale()]) : route('frontend.businesses.show', ['locale' => app()->getLocale(), 'business' => $businessId])"
            :cancel-label="__('general.actions.cancel')"
            :submit-label="__('general.actions.save')" />
    </form>
</div>
