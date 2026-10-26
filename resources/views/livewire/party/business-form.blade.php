<div>
    @if ($limitMessage)
        <div class="mb-6 rounded-sm border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-200">
            {{ $limitMessage }}
        </div>
    @endif

    <x-party.duplicates :blocking="$blockingDuplicate" :warnings="$duplicateWarnings" />

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-form.card :title="__('businesses.sections.details')">
                <x-party.details-fields :countries="$countries" :register-message="$registerMessage" />
            </x-form.card>

            <div class="space-y-6">
                <x-form.card :title="__('businesses.fields.logo')">
                    @if ($logo && $logo->isPreviewable())
                        <img src="{{ $logo->temporaryUrl() }}" alt="{{ __('businesses.fields.logo') }}" class="mb-4 max-h-24">
                    @elseif ($currentLogo && ! $removeLogo)
                        <img src="{{ asset('storage/' . $currentLogo) }}" alt="{{ __('businesses.fields.logo') }}" class="mb-4 max-h-24">
                    @endif
                    <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp"
                        class="block w-full text-sm text-gray-700 dark:text-gray-200">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('businesses.hints.logo') }}</p>
                    @error('logo')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if ($currentLogo)
                        <label class="mt-3 flex items-center text-sm text-gray-900 dark:text-white">
                            <input type="checkbox" wire:model.live="removeLogo" class="mr-2 rounded-sm">
                            {{ __('businesses.actions.remove_logo') }}
                        </label>
                    @endif
                </x-form.card>

                @if ($businessId === null)
                    <x-form.card :title="__('businesses.sections.first_bank_account')">
                        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ __('businesses.hints.first_bank_account') }}</p>
                        <x-party.bank-account-fields :bank-options="$bankOptions" :countries="$countries" />
                    </x-form.card>
                @endif
            </div>
        </div>

        <div class="mt-6 mb-20 flex justify-end gap-3">
            <a href="{{ $businessId === null ? route('frontend.businesses.index', ['locale' => app()->getLocale()]) : route('frontend.businesses.show', ['locale' => app()->getLocale(), 'business' => $businessId]) }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium">
                {{ __('general.actions.cancel') }}
            </a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="px-4 py-2 bg-[#490BF4] hover:bg-indigo-500 rounded-sm text-white text-sm font-bold uppercase tracking-wide">
                {{ __('general.actions.save') }}
            </button>
        </div>
    </form>
</div>
