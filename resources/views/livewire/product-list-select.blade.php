<div class="product-selector">
    <div class="mb-4">
        <div class="flex justify-between items-center">
            <div>
                <input wire:model.debounce.300ms="search" type="text"
                    class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                    placeholder="{{ __('products.actions.search') }}">
            </div>
            <div>
                <select wire:model="perPage"
                    class="border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
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
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
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
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
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
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
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
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
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
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($products as $product)
                <tr wire:key="{{ $product->id }}" wire:click="selectProduct({{ $product->id }})"
                    class="hover:bg-blue-50 cursor-pointer transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $product->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $product->category->name ?? __('general.empty.not_specified') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $product->price }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $product->currency ?: 'CZK' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
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
