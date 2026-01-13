<div class="product-selector">
    <div class="mb-4">
        <div class="flex justify-between items-center">
            <div>
                <input wire:model.debounce.300ms="search" type="text"
                    class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                    placeholder="{{ __('products.actions.search') }}">
            </div>
            <div>
                <select wire:model="perPage"
                    class="form-select block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('name')">
                        {{ __('products.fields.name') }}
                        @if ($sortField === 'name')
                        <span class="ml-1">
                            @if ($sortDirection === 'asc')
                            <i class="fas fa-sort-up"></i>
                            @else
                            <i class="fas fa-sort-down"></i>
                            @endif
                        </span>
                        @endif
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('category_id')">
                        {{ __('products.fields.category_id') }}
                        @if ($sortField === 'category')
                        <span class="ml-1">
                            @if ($sortDirection === 'asc')
                            <i class="fas fa-sort-up"></i>
                            @else
                            <i class="fas fa-sort-down"></i>
                            @endif
                        </span>
                        @endif
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('price')">
                        {{ __('products.fields.price') }}
                        @if ($sortField === 'price')
                        <span class="ml-1">
                            @if ($sortDirection === 'asc')
                            <i class="fas fa-sort-up"></i>
                            @else
                            <i class="fas fa-sort-down"></i>
                            @endif
                        </span>
                        @endif
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('currency')">
                        {{ __('products.fields.currency') }}
                        @if ($sortField === 'currency')
                        <span class="ml-1">
                            @if ($sortDirection === 'asc')
                            <i class="fas fa-sort-up"></i>
                            @else
                            <i class="fas fa-sort-down"></i>
                            @endif
                        </span>
                        @endif
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('tax_id')">
                        {{ __('products.fields.tax_id') }}
                        @if ($sortField === 'tax_id')
                        <span class="ml-1">
                            @if ($sortDirection === 'asc')
                            <i class="fas fa-sort-up"></i>
                            @else
                            <i class="fas fa-sort-down"></i>
                            @endif
                        </span>
                        @endif
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                @forelse ($products as $product)
                <tr wire:key="{{ $product->id }}" wire:click="selectProduct({{ $product->id }})"
                    class="hover:bg-blue-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {{ $product->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $product->category->name ?? __('general.empty.not_specified') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $product->price }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $product->currency ?: 'CZK' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        @if($product->tax)
                        {{ $product->tax->rate }}%
                        @elseif($product->tax_id)
                        {{ __('products.messages.tax_not_loaded') }} (ID: {{ $product->tax_id }})
                        @else
                        -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                        {{ __('products.messages.no_products_found') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>

    <div id="component-id-holder" data-component-id="{{ $_instance->getId() }}" style="display: none;"></div>
</div>

@push('scripts')
@vite('resources/js/product-selector.js')
@endpush
