<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\Expense;

use App\Domain\Shared\Money\Enums\Currency;
use App\Infrastructure\Forms\Party\AdminDocumentFormFields;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ExpenseFormFields
{
    /** @return array<string, string> */
    public static function supplierFilter(): array
    {
        return [
            'name' => 'contact_id',
            'type' => 'select2',
            'label' => __('expenses.fields.supplier'),
        ];
    }

    /** @return array<string, string> */
    public static function categoryFilter(): array
    {
        return [
            'name' => 'category_id',
            'type' => 'select2',
            'label' => __('expenses.fields.category_id'),
        ];
    }

    /**
     * Inactive categories included: a filter finds stored expenses, which may hold a category no longer offered.
     *
     * @return array<int, string> category name by id
     */
    public static function categoryNames(): array
    {
        $categoryQuery = ExpenseCategory::query();
        $names = $categoryQuery->pluck('name', 'id');

        return $names->all();
    }

    /** @return array<string, string> */
    public static function statusFilter(): array
    {
        return [
            'name' => 'status_id',
            'type' => 'select2',
            'label' => __('expenses.fields.status_id'),
        ];
    }

    /**
     * @param  array<string, string>  $countries
     * @param  array<string, string>  $currencies  currency labels by code
     * @return list<array<string, mixed>>
     */
    public static function fields(
        array $countries,
        array $currencies,
        ?Expense $document,
    ): array {
        $fields = [];
        $half = ['class' => 'form-group col-12 col-md-6'];
        foreach (
            [
                'expense_date' => 'datepicker',
                'reference_number' => 'text',
                'amount' => 'number',
                'tax_amount' => 'number',
                'description' => 'textarea',
            ] as $name => $type
        ) {
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
            'options' => $currencies,
            'default' => Currency::CZK->value,
            'wrapper' => $half,
        ];
        foreach (
            [
                'category_id' => ['category', ExpenseCategory::class],
                'payment_method_id' => ['paymentMethod', PaymentMethod::class],
                'status_id' => ['status', Status::class],
            ] as $name => [$entity, $model]
        ) {
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
                $field['options'] = function (Builder $query): Collection {
                    $query->whereHas(
                        'category',
                        fn (Builder $category) => $category->where('slug', 'expense-statuses'),
                    );
                    $query->where('is_active', true);

                    return $query->get();
                };
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
        $query = Tax::where('slug', 'dph');
        $taxRates = $query->pluck('name', 'rate');
        $fields[] = [
            'name' => 'tax_rate',
            'label' => __('expenses.fields.tax_rate'),
            'type' => 'select_from_array',
            'options' => $taxRates->all(),
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
        $parties = AdminDocumentFormFields::parties('supplier', $document);

        return [
            AdminDocumentFormFields::section(
                'selection',
                __('parties.sections.document_parties'),
            ),
            ...$parties,
            AdminDocumentFormFields::section(
                'document',
                __('expenses.sections.document'),
            ),
            $fields['expense_date'],
            $fields['reference_number'],
            $fields['payment_method_id'],
            AdminDocumentFormFields::section(
                'business',
                __('expenses.fields.customer'),
            ),
            AdminDocumentFormFields::businessPreview('customer', $document),
            AdminDocumentFormFields::section(
                'counterparty',
                __('expenses.fields.supplier'),
            ),
            ...AdminDocumentFormFields::counterparty('supplier', $countries),
            AdminDocumentFormFields::supplierLogo('contact', $document),
            AdminDocumentFormFields::section(
                'account',
                __('parties.sections.supplier_account'),
            ),
            [
                'name' => 'document_account_mode',
                'type' => 'document_account_mode',
            ],
            ...AdminDocumentFormFields::accountFields($countries, true),
            AdminDocumentFormFields::section(
                'amounts',
                __('expenses.sections.amounts'),
            ),
            $fields['amount'],
            $fields['currency'],
            $fields['tax_rate'],
            $fields['tax_included'],
            $fields['tax_amount'],
            AdminDocumentFormFields::section(
                'status',
                __('expenses.sections.status'),
            ),
            $fields['status_id'],
            $fields['category_id'],
            AdminDocumentFormFields::section(
                'other',
                __('expenses.sections.other'),
            ),
            $fields['description'],
            $fields['attachments'],
        ];
    }
}
