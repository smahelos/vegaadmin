<div>
    @if(isset($suppliers) && $suppliers->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('name')">
                            {{ __('suppliers.fields.name') }}
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
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('email')">
                            {{ __('suppliers.fields.email') }}
                            @if($orderBy === 'email')
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
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('phone')">
                            {{ __('suppliers.fields.phone') }}
                            @if($orderBy === 'phone')
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
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('ico')">
                            {{ __('suppliers.fields.ico') }}
                            @if($orderBy === 'ico')
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
                            {{ __('suppliers.fields.is_default') }}
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
                            {{ __('suppliers.fields.invoices') }}
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
                        __('suppliers.fields.actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($suppliers as $supplier)
                @php
                $confirmDeleteTxt = __('suppliers.messages.confirm_delete')
                @endphp
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $supplier->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $supplier->email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $supplier->phone ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $supplier->ico ?? '—' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm @if($supplier->is_default == 1)text-green-600 font-semibold @else text-gray-500 @endif">{{ $supplier->is_default ? __('general.yes') : __('general.no') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $supplier->invoices_count ??
                        $supplier->invoices->count() }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <!-- View button -->
                        <a href="{{ route('frontend.supplier.show', ['id' => $supplier->id, 'locale' => app()->getLocale()]) }}"
                            title="{{ __('suppliers.actions.show') }}" class="text-cyan-600 hover:text-cyan-900 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <!-- Edit button -->
                        <a href="{{ route('frontend.supplier.edit', ['id' => $supplier->id, 'locale' => app()->getLocale()]) }}"
                            title="{{ __('suppliers.actions.edit') }}"
                            class="text-yellow-600 hover:text-yellow-900 mr-3">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <!-- New invoice button -->
                        <a href="{{ route('frontend.invoice.create', ['supplier_id' => $supplier->id, 'locale' => app()->getLocale()]) }}"
                            title="{{ __('invoices.actions.create') }}"
                            class="text-green-600 hover:text-green-900 mr-3">
                            <i class="fas fa-file-invoice"></i>
                        </a>
                        <!-- Delete button -->
                        <a href="#" title="{{ __('suppliers.actions.delete') }}" class="text-red-600 hover:text-red-900"
                            onclick="event.preventDefault(); if(confirm('@php echo $confirmDeleteTxt; @endphp')) document.getElementById('delete-form-{{ $supplier->id }}').submit();">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                        <form id="delete-form-{{ $supplier->id }}"
                            action="{{ route('frontend.supplier.destroy', ['id' => $supplier->id, 'locale' => app()->getLocale()]) }}" method="POST" class="hidden">
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
    @if($suppliers instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="mt-4">
        <x-pagination :paginator="$suppliers" />
    </div>
    @endif
    @else
    <!-- Empty state message -->
    <div class="text-center py-10">
        <div class="text-gray-400 mb-3">
            <i class="fas fa-users fa-3x"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('suppliers.titles.empty') }}</h3>
        <p class="text-gray-500 mb-6">{{ __('suppliers.titles.empty_message') }}</p>
        <a href="{{ route('frontend.supplier.create', ['locale' => app()->getLocale()]) }}"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <i class="fas fa-plus mr-2"></i> {{ __('suppliers.actions.new') }}
        </a>
    </div>
    @endif
</div>
