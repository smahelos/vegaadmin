<div>
    @if(isset($products) && $products->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('name')">
                            {{ __('products.fields.name') }}
                            @if($orderBy === 'name')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('supplier_id')">
                            {{ __('invoices.fields.supplier_id') }}
                            @if($orderBy === 'supplier_id')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('price')">
                            {{ __('products.fields.price') }}
                            @if($orderBy === 'price')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('tax_id')">
                            {{ __('products.fields.tax_id') }}
                            @if($orderBy === 'tax_id')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('category_id')">
                            {{ __('products.fields.category_id') }}
                            @if($orderBy === 'category_id')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('is_default')">
                            {{ __('products.fields.is_default') }}
                            @if($orderBy === 'is_default')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('invoices')">
                            {{ __('products.titles.invoices') }}
                            @if($orderBy === 'invoices')
                                <span class="ml-1">
                                    @if($orderAsc)
                                        <i class="fas fa-sort-up text-indigo-600"></i>
                                    @else
                                        <i class="fas fa-sort-down text-indigo-600"></i>
                                    @endif
                                </span>
                            @else
                                <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{
                        __('products.actions.actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($products as $product)
                @php
                    $confirmDeleteTxt = __('products.messages.confirm_delete')
                @endphp
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->supplier->name ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->price ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->tax->name ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->category->name ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm @if($product->is_default == 1)text-green-600 font-semibold @else text-gray-500 @endif">{{ $product->is_default ? __('general.yes') : __('general.no') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->invoices_count ??
                        $product->invoices->count() }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <!-- View button -->
                        <a href="{{ route('frontend.product.show', ['id' => $product->id, 'locale' => app()->getLocale()]) }}"
                            title="{{ __('products.actions.view') }}" class="text-cyan-600 hover:text-cyan-900 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <!-- Edit button -->
                        <a href="{{ route('frontend.product.edit', ['id' => $product->id, 'locale' => app()->getLocale()]) }}"
                            title="{{ __('products.actions.edit') }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <!-- Delete button -->
                        <a href="#" title="{{ __('products.actions.delete') }}" class="text-red-600 hover:text-red-900"
                            onclick="event.preventDefault(); if(confirm('@php echo $confirmDeleteTxt; @endphp')) document.getElementById('delete-form-{{ $product->id }}').submit();">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                        <form id="delete-form-{{ $product->id }}"
                            action="{{ route('frontend.product.destroy', ['id' => $product->id, 'locale' => app()->getLocale()]) }}" method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="mt-4">
        <x-pagination :paginator="$products" />
    </div>
    @endif
    @else
    <!-- Empty state message -->
    <div class="text-center py-10">
        <div class="text-gray-400 mb-3">
            <i class="fas fa-users fa-3x"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('products.empty.title') }}</h3>
        <p class="text-gray-500 mb-6">{{ __('products.empty.message') }}</p>
        <a href="{{ route('frontend.product.create', ['locale' => app()->getLocale()]) }}"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <i class="fas fa-plus mr-2"></i> {{ __('products.actions.new') }}
        </a>
    </div>
    @endif
</div>
