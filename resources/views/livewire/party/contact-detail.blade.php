<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white leading-tight pt-3">
            <span class="text-base font-medium">{{ __('contacts.entity.singular') }}</span>
            {{ $contact->profile->name }}
            @if ($contact->isArchived())
                <span class="ml-2 align-middle rounded-full bg-gray-200 px-3 py-1 text-sm font-semibold text-gray-700">{{ __('parties.statuses.archived') }}</span>
            @endif
        </h1>
        <div class="flex flex-wrap items-center gap-2 md:gap-4">
            <x-back-button />
            <x-icon-button icon="fa-list" :label="__('contacts.actions.back_to_list')"
                :href="route('frontend.contacts.index', ['locale' => app()->getLocale()])" />
            @can('frontend.can_update_contact')
                <x-icon-button icon="fa-pencil-alt" tone="pink" :label="__('general.actions.edit')"
                    :href="route('frontend.contacts.edit', ['locale' => app()->getLocale(), 'contact' => $contact->id->value])" />
            @endcan
            @if ($contact->isArchived())
                @can('frontend.can_restore_contact')
                    <x-icon-button icon="fa-undo" tone="emerald" :label="__('parties.actions.restore')" wire:click="restore" />
                @endcan
            @else
                @can('frontend.can_archive_contact')
                    <x-icon-button icon="fa-archive" tone="orange" :label="__('parties.actions.archive')"
                        wire:click="archive" wire:confirm="{{ __('contacts.messages.confirm_archive') }}" />
                @endcan
            @endif
        </div>
    </div>

    <x-status-messages :error="$errorMessage" :success="$successMessage" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <x-detail.card :title="__('parties.sections.basic')" :badge="__('contacts.entity.singular')">
            <x-party.basic-profile :profile="$contact->profile">
                <x-detail.item class="md:col-span-2" accent="border-yellow-500" :label="__('contacts.fields.contact_person')">{{ $contact->contactPerson }}</x-detail.item>
                <x-party.logo-profile :logo="$contact->logo" />
            </x-party.basic-profile>
        </x-detail.card>
        <x-detail.card :title="__('parties.sections.billing')">
            <x-party.billing-profile :profile="$contact->profile" :countries="$countries" />
        </x-detail.card>
    </div>

    <livewire:party.bank-accounts owner-type="contact" :owner-id="$contact->id->value" />
</div>
