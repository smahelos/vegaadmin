<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white leading-tight pt-3">
            {{ $contact->profile->name }}
            @if ($contact->isArchived())
                <span class="ml-2 align-middle rounded-sm bg-gray-200 px-2 py-0.5 text-sm font-semibold text-gray-700">{{ __('parties.statuses.archived') }}</span>
            @endif
        </h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('frontend.contacts.index', ['locale' => app()->getLocale()]) }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium">
                <i class="fas fa-list mr-1"></i> {{ __('contacts.actions.back_to_list') }}
            </a>
            @can('frontend.can_update_contact')
                <a href="{{ route('frontend.contacts.edit', ['locale' => app()->getLocale(), 'contact' => $contact->id->value]) }}"
                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 rounded-sm text-white text-sm font-medium">
                    <i class="fas fa-pencil-alt mr-1"></i> {{ __('general.actions.edit') }}
                </a>
            @endcan
            @if ($contact->isArchived())
                @can('frontend.can_restore_contact')
                    <button type="button" wire:click="restore" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-sm text-white text-sm font-medium">
                        <i class="fas fa-undo mr-1"></i> {{ __('parties.actions.restore') }}
                    </button>
                @endcan
            @else
                @can('frontend.can_archive_contact')
                    <button type="button" wire:click="archive" wire:confirm="{{ __('contacts.messages.confirm_archive') }}"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-sm text-white text-sm font-medium">
                        <i class="fas fa-archive mr-1"></i> {{ __('parties.actions.archive') }}
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <x-status-messages :error="$errorMessage" :success="$successMessage" />

    <x-form.card class="mb-6" :title="__('contacts.sections.details')">
        <x-party.profile :profile="$contact->profile" :countries="$countries" />
        @if ($contact->contactPerson)
            <div class="mt-4 text-sm">
                <span class="text-gray-500 dark:text-gray-400">{{ __('contacts.fields.contact_person') }}:</span>
                <span class="text-gray-900 dark:text-white">{{ $contact->contactPerson }}</span>
            </div>
        @endif
    </x-form.card>

    <livewire:party.bank-accounts owner-type="contact" :owner-id="$contact->id->value" />
</div>
