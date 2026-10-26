<div>
    <x-form.card class="mb-20" :title="__('parties.bank_accounts.entity.plural')">
        <x-status-messages :error="$errorMessage" :success="$successMessage" />

        @if ($accounts === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('parties.bank_accounts.messages.none') }}</p>
        @else
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($accounts as $bankAccount)
                    <li wire:key="account-{{ $bankAccount->id->value }}" class="py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="text-sm">
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ $bankAccount->label ?? __('parties.bank_accounts.entity.singular') }}
                                @if ($defaultId !== null && $defaultId->equals($bankAccount->id))
                                    <span class="ml-2 rounded-sm bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">{{ __('parties.bank_accounts.fields.default') }}</span>
                                @endif
                                @if ($bankAccount->isArchived())
                                    <span class="ml-2 rounded-sm bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ __('parties.statuses.archived') }}</span>
                                @endif
                            </p>
                            <p class="text-gray-700 dark:text-gray-300">{{ $bankAccount->account->formattedIban() }}</p>
                            @if ($bankAccount->account->number)
                                <p class="text-gray-500 dark:text-gray-400">{{ $bankAccount->account->number }}/{{ $bankAccount->account->bankCode }}</p>
                            @endif
                        </div>
                        @if ($canUpdate)
                            <div class="flex flex-wrap gap-2 text-sm">
                                @if ($bankAccount->isArchived())
                                    <button type="button" wire:click="restore({{ $bankAccount->id->value }})" class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-undo mr-1"></i>{{ __('parties.actions.restore') }}
                                    </button>
                                @else
                                    <button type="button" wire:click="edit({{ $bankAccount->id->value }})" class="text-yellow-600 hover:text-yellow-800">
                                        <i class="fas fa-pencil-alt mr-1"></i>{{ __('general.actions.edit') }}
                                    </button>
                                    @if ($defaultId === null || ! $defaultId->equals($bankAccount->id))
                                        <button type="button" wire:click="makeDefault({{ $bankAccount->id->value }})" class="text-[#490BF4] hover:text-indigo-800">
                                            <i class="fas fa-star mr-1"></i>{{ __('parties.bank_accounts.actions.make_default') }}
                                        </button>
                                    @endif
                                    <button type="button" wire:click="archive({{ $bankAccount->id->value }})" class="text-gray-600 hover:text-gray-900 dark:text-gray-300">
                                        <i class="fas fa-archive mr-1"></i>{{ __('parties.actions.archive') }}
                                    </button>
                                @endif
                            </div>
                        @endif
                    </li>
                    @if ($archivingId === $bankAccount->id->value)
                        <li class="py-4">
                            <p class="mb-3 text-sm text-gray-700 dark:text-gray-300">{{ __('parties.bank_accounts.messages.choose_new_default') }}</p>
                            <div class="flex flex-col md:flex-row gap-3 md:items-end">
                                <x-form.select class="md:w-96" model="newDefault" :label="__('parties.bank_accounts.fields.new_default')"
                                    :options="$newDefaultOptions" />
                                <button type="button" wire:click="archive({{ $bankAccount->id->value }})"
                                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-sm text-white text-sm font-medium">
                                    {{ __('parties.actions.archive') }}
                                </button>
                                <button type="button" wire:click="cancel"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium">
                                    {{ __('general.actions.cancel') }}
                                </button>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif

        @if ($canUpdate)
            @if ($editing)
                <form wire:submit="save" class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ $editingId === null ? __('parties.bank_accounts.titles.create') : __('parties.bank_accounts.titles.edit') }}
                    </h3>
                    <x-party.bank-account-fields :bank-options="$bankOptions" :countries="$countries" />
                    <div class="mt-4 flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-[#490BF4] hover:bg-indigo-500 rounded-sm text-white text-sm font-bold">
                            {{ __('general.actions.save') }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium">
                            {{ __('general.actions.cancel') }}
                        </button>
                    </div>
                </form>
            @else
                <button type="button" wire:click="add" class="mt-6 px-4 py-2 bg-indigo-50 text-[#490BF4] hover:bg-[#490BF4] hover:text-white rounded-sm text-sm font-semibold">
                    <i class="fas fa-plus mr-1"></i> {{ __('parties.bank_accounts.actions.add') }}
                </button>
            @endif
        @endif
    </x-form.card>
</div>
