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
                <x-party.basic-fields>
                    <x-form.input model="contactPerson" :label="__('contacts.fields.contact_person')"
                        :hint="__('contacts.hints.contact_person')" />
                </x-party.basic-fields>
                <x-party.logo-fields :logo="$logo" :current-logo="$currentLogo" :remove-logo="$removeLogo" />
            </x-form.card>

            <x-form.card :title="__('parties.sections.billing')">
                <x-party.billing-fields :countries="$countries" :register-message="$registerMessage" />
            </x-form.card>
        </div>

        <x-form.actions
            :cancel-href="$contactId === null ? route('frontend.contacts.index', ['locale' => app()->getLocale()]) : route('frontend.contacts.show', ['locale' => app()->getLocale(), 'contact' => $contactId])"
            :cancel-label="__('general.actions.cancel')"
            :submit-label="__('general.actions.save')" />
    </form>
</div>
