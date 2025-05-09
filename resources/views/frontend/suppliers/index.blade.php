@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl text-amber-600">{{ __('suppliers.titles.index') }}</h1>
    <a href="@localizedRoute('frontend.supplier.create')" class="inline-flex items-center px-4 py-2 bg-blue-300 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        <i class="fas fa-plus mr-2"></i> {{ __('suppliers.actions.new') }}
    </a>
</div>

<div class="bg-white overflow-hidden shadow-sm rounded-lg">
    <div class="p-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('suppliers.fields.name') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('suppliers.fields.email') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('suppliers.fields.phone') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('suppliers.fields.ico') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('suppliers.fields.city') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('suppliers.fields.invoices') }}
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">{{ __('suppliers.actions.edit') }}</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($suppliers as $supplier)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $supplier->name }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">{{ $supplier->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">{{ $supplier->phone }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">{{ $supplier->ico }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">{{ $supplier->city }}</div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $supplier->invoices_count ?? $supplier->invoices->count() }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="@localizedRoute('frontend.supplier.show', $supplier)" title="{{ __('suppliers.actions.show') }}" class="text-cyan-600 hover:text-cyan-900 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="@localizedRoute('frontend.supplier.edit', $supplier)" title="{{ __('suppliers.actions.edit') }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <a href="@localizedRoute('frontend.invoice.create', ['supplier_id' => $supplier->id])" title="{{ __('suppliers.actions.invoice') }}" class="text-green-600 hover:text-green-900 mr-3">
                            <i class="fas fa-file-invoice"></i>
                        </a>
                        <a href="#" class="text-red-600 hover:text-red-900" title="{{ __('suppliers.actions.delete') }}" onclick="event.preventDefault(); if(confirm('{{ __('suppliers.messages.confirm_delete') }}')) document.getElementById('delete-form-{{ $supplier->id }}').submit();">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                        <form id="delete-form-{{ $supplier->id }}" action="{{ route('frontend.supplier.destroy', $supplier) }}" method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination -->
        @if($suppliers instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="mt-4">
                <x-pagination :paginator="$suppliers" />
            </div>
        @endif
    </div>
</div>
@endsection
