<div>
    @if ($limitMessage)
        <div class="mb-6 rounded-sm border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-200">
            {{ $limitMessage }}
        </div>
    @endif

    <x-party.duplicates :blocking="$blockingDuplicate" :warnings="$duplicateWarnings" />

    <form wire:submit="save">
        <x-form.card :title="__('contacts.sections.details')">
            <x-party.details-fields :countries="$countries" :register-message="$registerMessage" />
            <x-form.input class="mt-5" model="contactPerson" :label="__('contacts.fields.contact_person')"
                :hint="__('contacts.hints.contact_person')" />
        </x-form.card>

        <div class="mt-6 mb-20 flex justify-end gap-3">
            <a href="{{ $contactId === null ? route('frontend.contacts.index', ['locale' => app()->getLocale()]) : route('frontend.contacts.show', ['locale' => app()->getLocale(), 'contact' => $contactId]) }}"
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
