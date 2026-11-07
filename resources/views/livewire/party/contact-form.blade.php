<div>
    <x-limit-exceeded :message="$limitMessage" />

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
            :cancel-href="$cancelHref"
            :cancel-label="__('general.actions.cancel')"
            :submit-label="__('general.actions.save')" />
    </form>
</div>
