<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white leading-tight pt-3">
            {{ $business->profile->name }}
            @if ($isDefault)
                <span class="ml-2 align-middle rounded-sm bg-green-100 px-2 py-0.5 text-sm font-semibold text-green-700">{{ __('businesses.fields.default') }}</span>
            @endif
            @if ($business->isArchived())
                <span class="ml-2 align-middle rounded-sm bg-gray-200 px-2 py-0.5 text-sm font-semibold text-gray-700">{{ __('parties.statuses.archived') }}</span>
            @endif
        </h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('frontend.businesses.index', ['locale' => app()->getLocale()]) }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium">
                <i class="fas fa-list mr-1"></i> {{ __('businesses.actions.back_to_list') }}
            </a>
            @can('frontend.can_update_business')
                <a href="{{ route('frontend.businesses.edit', ['locale' => app()->getLocale(), 'business' => $business->id->value]) }}"
                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 rounded-sm text-white text-sm font-medium">
                    <i class="fas fa-pencil-alt mr-1"></i> {{ __('general.actions.edit') }}
                </a>
            @endcan
            @if ($business->isArchived())
                @can('frontend.can_restore_business')
                    <button type="button" wire:click="restore" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-sm text-white text-sm font-medium">
                        <i class="fas fa-undo mr-1"></i> {{ __('parties.actions.restore') }}
                    </button>
                @endcan
            @else
                @if (! $isDefault)
                    @can('frontend.can_set_default_business')
                        <button type="button" wire:click="makeDefault" class="px-4 py-2 bg-indigo-50 text-[#490BF4] hover:bg-[#490BF4] hover:text-white rounded-sm text-sm font-medium">
                            <i class="fas fa-star mr-1"></i> {{ __('businesses.actions.make_default') }}
                        </button>
                    @endcan
                @endif
                @can('frontend.can_archive_business')
                    <button type="button" wire:click="archive" wire:confirm="{{ __('businesses.messages.confirm_archive') }}"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-sm text-white text-sm font-medium">
                        <i class="fas fa-archive mr-1"></i> {{ __('parties.actions.archive') }}
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <x-status-messages :error="$errorMessage" :success="$successMessage" />

    @if ($choosingNewDefault)
        <x-form.card class="mb-6" :title="__('businesses.titles.choose_new_default')">
            <p class="mb-4 text-sm text-gray-700 dark:text-gray-300">{{ __('businesses.messages.choose_new_default') }}</p>
            <x-form.select model="newDefault" :label="__('businesses.fields.new_default')"
                :options="$newDefaultOptions" />
            <div class="mt-4 flex gap-2">
                <button type="button" wire:click="archive" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-sm text-white text-sm font-medium">
                    {{ __('parties.actions.archive') }}
                </button>
                <button type="button" wire:click="cancelArchive" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium">
                    {{ __('general.actions.cancel') }}
                </button>
            </div>
        </x-form.card>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <x-form.card class="lg:col-span-2" :title="__('businesses.sections.details')">
            <x-party.profile :profile="$business->profile" :countries="$countries" />
        </x-form.card>
        <x-form.card :title="__('businesses.fields.logo')">
            @if ($business->logo)
                <img src="{{ asset('storage/' . $business->logo) }}" alt="{{ __('businesses.fields.logo') }}" class="max-h-32">
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('businesses.messages.no_logo') }}</p>
            @endif
        </x-form.card>
    </div>

    <livewire:party.bank-accounts owner-type="business" :owner-id="$business->id->value" />
</div>
