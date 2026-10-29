@props(['logo', 'currentLogo', 'removeLogo'])

                <div class="mt-5">
                    <span class="block text-base font-medium text-gray-900 dark:text-white mb-2">{{ __('parties.fields.logo') }}</span>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <label for="logo" x-data="{ over: false }"
                            x-on:dragover.prevent="over = true" x-on:dragleave.prevent="over = false"
                            x-on:drop.prevent="over = false; $wire.upload('logo', $event.dataTransfer.files[0])"
                            :class="over ? 'border-[#490BF4] bg-indigo-50 dark:bg-gray-800' : 'border-gray-300 bg-gray-50 dark:bg-gray-700 dark:border-gray-600'"
                            class="flex flex-col items-center justify-center w-full h-37 border-2 border-dashed rounded-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 dark:hover:border-gray-500 transition-colors duration-200">
                            <span class="flex flex-col items-center justify-center p-3 text-center">
                                <span class="mb-2 text-lg font-semibold text-[#490BF4] dark:text-gray-200">
                                    <i class="fas fa-spinner fa-spin mr-1" wire:loading wire:target="logo"></i>
                                    {{ __('parties.actions.upload_logo') }}
                                </span>
                                <span class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('parties.hints.logo') }}</span>
                            </span>
                            <input id="logo" type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="hidden">
                        </label>
                        <div class="flex items-center justify-center h-37">
                            @if ($logo && $logo->isPreviewable())
                                <img src="{{ $logo->temporaryUrl() }}" alt="{{ __('parties.fields.logo') }}" class="max-h-37 object-contain rounded-sm">
                            @elseif ($currentLogo && ! $removeLogo)
                                <img src="{{ asset('storage/' . $currentLogo) }}" alt="{{ __('parties.fields.logo') }}" class="max-h-37 object-contain rounded-sm">
                            @else
                                <div class="flex flex-col items-center justify-center w-full h-full rounded-sm bg-gray-50 dark:bg-gray-700 text-gray-400">
                                    <i class="fas fa-image fa-3x mb-2"></i>
                                    <span class="text-sm">{{ __('parties.messages.no_logo') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @error('logo')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if ($currentLogo)
                        <label class="mt-3 flex items-center text-sm text-gray-900 dark:text-white">
                            <input type="checkbox" wire:model.live="removeLogo" class="mr-2 rounded-sm">
                            {{ __('parties.actions.remove_logo') }}
                        </label>
                    @endif
                </div>
