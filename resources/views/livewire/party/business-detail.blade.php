@use('App\Application\Party\Commands\ArchiveBusiness')
@use('App\Application\Party\Commands\RestoreBusiness')
@use('App\Application\Party\Commands\SetDefaultBusiness')
@use('App\Application\Party\Commands\UpdateBusiness')
@use('App\Application\Party\Queries\ListBankAccounts')
<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white leading-tight pt-3">
            <span class="text-base font-medium">{{ __('businesses.entity.singular') }}</span>
            {{ $business->profile->name }}
            @if ($isDefault)
                <span class="ml-2 align-middle rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">{{ __('businesses.fields.default') }}</span>
            @endif
            @if ($business->isArchived())
                <span class="ml-2 align-middle rounded-full bg-gray-200 px-3 py-1 text-sm font-semibold text-gray-700">{{ __('parties.statuses.archived') }}</span>
            @endif
        </h1>
        <div class="flex flex-wrap items-center gap-2 md:gap-4">
            <x-back-button />
            <x-icon-button icon="fa-list" :label="__('businesses.actions.back_to_list')"
                :href="route('frontend.businesses.index', ['locale' => App::getLocale()])" />
            @allowed(UpdateBusiness::class)
                <x-icon-button icon="fa-pencil-alt" tone="pink" :label="__('general.actions.edit')"
                    :href="route('frontend.businesses.edit', ['locale' => App::getLocale(), 'business' => $business->id->value])" />
            @endallowed
            @if ($business->isArchived())
                @allowed(RestoreBusiness::class)
                    <x-icon-button icon="fa-undo" tone="emerald" :label="__('parties.actions.restore')" wire:click="restore" />
                @endallowed
            @else
                @if (! $isDefault)
                    @allowed(SetDefaultBusiness::class)
                        <x-icon-button icon="fa-star" tone="yellow" :label="__('businesses.actions.make_default')" wire:click="makeDefault" />
                    @endallowed
                @endif
                @allowed(ArchiveBusiness::class)
                    <x-icon-button icon="fa-archive" tone="orange" :label="__('parties.actions.archive')"
                        wire:click="archive" wire:confirm="{{ __('businesses.messages.confirm_archive') }}" />
                @endallowed
            @endif
        </div>
    </div>

    <x-status-messages :error="$errorMessage" :success="$successMessage" />
    <x-limit-exceeded :message="$limitMessage" />

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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <x-detail.card :title="__('parties.sections.basic')" :badge="__('businesses.entity.singular')">
            <x-party.basic-profile :profile="$business->profile">
                <x-party.logo-profile :logo="$business->logo" />
            </x-party.basic-profile>
        </x-detail.card>
        <x-detail.card :title="__('parties.sections.billing')">
            <x-party.billing-profile :profile="$business->profile" :countries="$countries" />
        </x-detail.card>
    </div>

    @allowed(ListBankAccounts::class)
        <livewire:party.bank-accounts owner-type="business" :owner-id="$business->id->value" />
    @endallowed
</div>
