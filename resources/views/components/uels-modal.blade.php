{{--
UELS Details Modal Component
Shows detailed information about entity limits and usage
--}}
@props([
'entityTypes' => ['client', 'supplier', 'product', 'invoice']
])

<!-- UELS Modal -->
<div id="uelsModal" class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50 hidden transition-opacity opacity-0 duration-500">
    <div
        class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="uelsModalTitle">
                    <i class="fas fa-shield-alt mr-2 text-purple-500"></i>
                    {{ __('uels.modal.title') }}
                </h3>
                <button type="button" onclick="closeUelsModal()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors cursor-pointer">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="py-4">
                <!-- Loading State -->
                <div id="uelsModalLoading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                    <p class="mt-2 text-gray-600 dark:text-gray-300">{{ __('uels.modal.loading') }}</p>
                </div>

                <!-- Content Container -->
                <div id="uelsModalContent" class="hidden space-y-6">
                    <!-- Entity Overview -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                            <i class="fas fa-chart-bar mr-2"></i>
                            {{ __('uels.modal.overview') }}
                        </h4>
                        <div id="uelsEntityOverview" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Current Period Usage -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                            <i class="fas fa-calendar mr-2"></i>
                            {{ __('uels.modal.current_period') }}
                        </h4>
                        <div id="uelsCurrentUsage"
                            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                            <i class="fas fa-history mr-2"></i>
                            {{ __('uels.modal.recent_activity') }}
                        </h4>
                        <div id="uelsRecentActivity" class="space-y-2">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div id="uelsModalError" class="hidden text-center py-8">
                    <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-300">{{ __('uels.modal.error') }}</p>
                    <button onclick="retryUelsModal()"
                        class="mt-4 px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600 transition-colors cursor-pointer">
                        {{ __('uels.modal.retry') }}
                    </button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-600 space-x-3">
                <button type="button" onclick="refreshUelsData()"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors cursor-pointer">
                    <i class="fas fa-sync-alt mr-2"></i>
                    {{ __('uels.modal.refresh') }}
                </button>
                <button type="button" onclick="closeUelsModal()"
                    class="px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600 transition-colors cursor-pointer">
                    {{ __('uels.modal.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
