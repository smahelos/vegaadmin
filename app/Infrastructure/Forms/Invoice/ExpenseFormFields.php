<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\Invoice;

use App\Domain\Shared\Money\Enums\Currency;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Tax;

final class ExpenseFormFields
{
    /**
     * @param  array<string, string>  $countries
     * @return list<array<string, mixed>>
     */
    public static function fields(array $countries, ?Expense $document): array
    {
        $fields = [];
        $half = ['class' => 'form-group col-12 col-md-6'];
        foreach ([
            'expense_date' => 'datepicker',
            'reference_number' => 'text',
            'amount' => 'number',
            'tax_amount' => 'number',
            'description' => 'textarea',
        ] as $name => $type) {
            $field = [
                'name' => $name,
                'label' => __('expenses.fields.' . $name),
                'type' => $type,
                'wrapper' => $half,
            ];
            if ($type === 'number') {
                $field['attributes'] = ['step' => '0.01'];
            }
            $fields[] = $field;
        }
        $fields[] = [
            'name' => 'currency',
            'label' => __('expenses.fields.currency'),
            'type' => 'select2_from_array',
            'options' => array_combine(array_column(Currency::cases(), 'value'), array_column(Currency::cases(), 'value')),
            'default' => Currency::CZK->value,
            'wrapper' => $half,
        ];
        foreach ([
            'category_id' => ['category', ExpenseCategory::class],
            'payment_method_id' => ['paymentMethod', PaymentMethod::class],
            'status_id' => ['status', Status::class],
        ] as $name => [$entity, $model]) {
            $field = [
                'name' => $name,
                'label' => __('expenses.fields.' . $name),
                'type' => 'select2',
                'entity' => $entity,
                'model' => $model,
                'attribute' => 'name',
                'wrapper' => $half,
            ];
            if ($name === 'status_id') {
                $field['options'] = fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', 'expense-statuses'))->where('is_active', true)->get();
            }
            $fields[] = $field;
        }
        $fields[] = [
            'name' => 'attachments',
            'label' => __('expenses.fields.attachments'),
            'type' => 'files_list',
            'upload' => true,
            'accept' => 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt',
            'hint' => __('expenses.hints.attachments'),
        ];
        $fields[] = [
            'name' => 'tax_rate',
            'label' => __('expenses.fields.tax_rate'),
            'type' => 'select_from_array',
            'options' => Tax::where('slug', 'dph')->pluck('name', 'rate')->all(),
            'wrapper' => $half,
        ];
        $fields[] = [
            'name' => 'tax_included',
            'label' => __('expenses.fields.tax_included'),
            'type' => 'checkbox',
            'default' => true,
            'wrapper' => ['class' => 'form-group col-12 col-md-6 tax_included_parent'],
        ];

        $fields = array_column(
            $fields,
            null,
            'name',
        );
        $fields['description']['wrapper'] = ['class' => 'form-group col-12'];

        return [
            AdminDocumentFormFields::section('selection', __('parties.sections.document_parties')),
            ...AdminDocumentFormFields::parties('supplier', $document),
            AdminDocumentFormFields::section('document', __('expenses.sections.document')),
            $fields['expense_date'],
            $fields['reference_number'],
            $fields['payment_method_id'],
            AdminDocumentFormFields::section('business', __('expenses.fields.customer')),
            AdminDocumentFormFields::businessPreview('customer', $document),
            AdminDocumentFormFields::section('counterparty', __('expenses.fields.supplier')),
            ...AdminDocumentFormFields::counterparty('supplier', $countries),
            AdminDocumentFormFields::section('account', __('parties.sections.supplier_account')),
            [
                'name' => 'document_account_mode',
                'type' => 'document_account_mode',
            ],
            ...AdminDocumentFormFields::accountFields($countries, true),
            AdminDocumentFormFields::section('amounts', __('expenses.sections.amounts')),
            $fields['amount'],
            $fields['currency'],
            $fields['tax_rate'],
            $fields['tax_included'],
            $fields['tax_amount'],
            AdminDocumentFormFields::section('status', __('expenses.sections.status')),
            $fields['status_id'],
            $fields['category_id'],
            AdminDocumentFormFields::section('other', __('expenses.sections.other')),
            $fields['description'],
            $fields['attachments'],
        ];
    }
}
