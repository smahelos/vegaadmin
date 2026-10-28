<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @if ($guest)
        <x-form.card :title="__('invoices.sections.supplier')">
            <div class="space-y-5">
                <x-party.document-contact-fields form="supplier" names="supplier" />
                <x-party.billing-fields :countries="$countries" :register-message="$supplierRegisterMessage" form="supplier"
                    look-up="lookUpSupplier" names="supplier" required />

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white pt-2">{{ __('invoices.sections.payment_info') }}</h3>
                <x-party.bank-account-fields :bank-options="$banks" :countries="$countries" names="account" :labelled="false" />
            </div>
        </x-form.card>
    @else
        <x-form.card :title="__('invoices.sections.supplier')">
            <div class="space-y-5">
                <x-form.select model="businessId" name="business_id" :label="__('invoices.fields.business_id')" :options="$businesses"
                    :placeholder="__('invoices.placeholders.select_business')" live required />
                <input type="hidden" name="reload_business" value="{{ $reloadBusiness ? 1 : 0 }}">

                @if ($business)
                    @if ($businessChanged)
                        <div class="rounded-sm border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200">
                            <p>{{ __('invoices.messages.business_changed') }}</p>
                            <button type="button" wire:click="reloadBusinessData" class="mt-2 font-semibold underline cursor-pointer">
                                {{ __('invoices.actions.reload_business') }}
                            </button>
                        </div>
                    @elseif ($reloadBusiness)
                        <p class="text-sm text-green-600">{{ __('invoices.messages.business_reloaded') }}</p>
                    @endif

                    @if ($supplierPreview)
                        <div>
                            <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $supplierPreview->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $supplierPreview->email }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $supplierPreview->phone }}</p>
                        </div>
                        <x-party.billing-profile :profile="$supplierPreview" :countries="$countries" />
                        @unless ($supplierPreview->hasCompleteAddress())
                            <p class="text-sm text-red-600">
                                {{ __('parties.validation.business_address_incomplete') }}
                                <a href="{{ route('frontend.businesses.edit', ['locale' => app()->getLocale(), 'business' => $business->id->value]) }}"
                                    class="font-semibold underline">{{ __('invoices.actions.edit_business') }}</a>
                            </p>
                        @endunless
                    @endif

                    @if ($accounts === [])
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('invoices.messages.no_active_account') }}
                            <a href="{{ route('frontend.businesses.show', ['locale' => app()->getLocale(), 'business' => $business->id->value]) }}"
                                class="font-semibold underline">{{ __('invoices.actions.add_account') }}</a>
                        </p>
                    @else
                        <x-form.select model="bankAccountId" name="bank_account_id" :label="__('invoices.fields.bank_account_id')"
                            :options="$accounts" :placeholder="__('invoices.placeholders.select_account')" live />
                        @if ($accountPreview)
                            <x-party.account-profile :account="$accountPreview" />
                        @endif
                    @endif
                @endif
            </div>
        </x-form.card>
    @endif

    <x-form.card :title="__('invoices.sections.customer')">
        <div class="space-y-5">
            @unless ($guest)
                <input type="hidden" name="contact_id" value="{{ $contactId }}">
                @if ($contact)
                    <div class="flex flex-wrap items-center gap-3 rounded-sm border border-indigo-100 bg-indigo-50 p-3 text-sm dark:border-gray-600 dark:bg-gray-700">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $contact->profile->name }}</span>
                        @if ($contact->isArchived())
                            <span class="text-gray-500 dark:text-gray-400">({{ __('parties.statuses.archived') }})</span>
                        @endif
                        <button type="button" wire:click="reloadContact" class="underline cursor-pointer">{{ __('invoices.actions.reload_contact') }}</button>
                        <button type="button" wire:click="clearContact" class="underline cursor-pointer">{{ __('invoices.actions.clear_contact') }}</button>
                    </div>
                @else
                    <div class="relative">
                        <label for="contactQuery" class="block text-base font-medium text-gray-900 dark:text-white mb-2">{{ __('invoices.fields.contact_id') }}</label>
                        <input type="search" id="contactQuery" wire:model.live.debounce.300ms="contactQuery" autocomplete="off"
                            placeholder="{{ __('invoices.placeholders.search_contact') }}"
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                        @if ($contactSearched)
                            <ul class="mt-1 rounded-sm border border-blue-100 bg-white shadow dark:border-gray-600 dark:bg-gray-800">
                                @forelse ($contactResults as $result)
                                    <li>
                                        <button type="button" wire:click="selectContact({{ $result->id->value }})"
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-gray-700 cursor-pointer">
                                            {{ $result->profile->name }}
                                            @if ($result->profile->registrationNumber)
                                                <span class="text-gray-500 dark:text-gray-400">· {{ $result->profile->registrationNumber->number }}</span>
                                            @endif
                                        </button>
                                    </li>
                                @empty
                                    <li class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ __('invoices.messages.no_contacts_found') }}</li>
                                @endforelse
                            </ul>
                        @endif
                    </div>
                @endif
                @error('contactId')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            @endunless

            <x-party.document-contact-fields form="customer" names="customer" />
            <x-party.billing-fields :countries="$countries" :register-message="$customerRegisterMessage" form="customer"
                look-up="lookUpCustomer" names="customer" required />

            @if (! $guest && $contactId === null && $canSaveToContacts)
                <label class="flex items-center gap-2 text-base text-gray-900 dark:text-white">
                    <input type="checkbox" name="save_customer_to_contacts" value="1" wire:model.live="saveToContacts"
                        class="rounded-sm border-blue-100 text-indigo-600 focus:ring-indigo-600">
                    {{ __('invoices.fields.save_customer_to_contacts') }}
                </label>
                @if ($existingContact)
                    <div class="rounded-sm border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200">
                        <p>{{ __('invoices.messages.contact_exists', ['name' => $existingContact->name]) }}</p>
                        @unless ($existingContact->archived)
                            <button type="button" wire:click="linkContact({{ $existingContact->id->value }})" class="mt-2 font-semibold underline cursor-pointer">
                                {{ __('invoices.actions.use_contact') }}
                            </button>
                        @endunless
                    </div>
                @endif
            @endif
        </div>
    </x-form.card>
</div>
