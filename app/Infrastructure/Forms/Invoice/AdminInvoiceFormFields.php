<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\Invoice;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\StatusCategory;

final class AdminInvoiceFormFields
{
    use InvoiceFormFields;

    /**
     * @param  array<string, string>  $countries
     * @return list<array<string, mixed>>
     */
    public function fields(array $countries, ?Invoice $document): array
    {
        $fields = [];
        $categoryId = StatusCategory::where('slug', 'invoice-statuses')->value('id');
        foreach ($this->getInvoiceFields() as $field) {
            if ($field['name'] === 'invoice_logo') {
                continue;
            }
            $field['wrapper'] = ['class' => 'form-group col-12 col-md-6'];
            if ($field['name'] === 'payment_amount') {
                $field['attributes'] = ['step' => '0.01'];
            }
            if ($document !== null) {
                unset($field['default']);
            }
            switch ($field['type']) {
                case 'date':
                    $field['type'] = 'datepicker';
                    break;
                case 'select_from_array':
                    $field['type'] = 'select2_from_array';
                    $current = $document?->getAttribute($field['name']);
                    if (filled($current) && ! array_key_exists($current, $field['options'])) {
                        $field['options'][$current] = $field['name'] === 'due_in' ? $current . ' ' . __('invoices.units.days') : $current;
                    }
                    break;
                case 'select':
                    $field['type'] = 'select2';
                    unset($field['options']);
                    if ($field['name'] === 'payment_status_id') {
                        $field['options'] = fn ($query) => $query->where('category_id', $categoryId)->where('is_active', true)->orderBy('name')->get();
                        if ($document === null) {
                            $field['default'] = Status::where('slug', InvoiceStatus::PENDING->value)->value('id');
                        }
                    } elseif ($document === null) {
                        $field['default'] = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->value('id');
                    }
                    break;
                case 'file':
                    $field['type'] = 'upload';
                    break;
            }
            $fields[$field['name']] = $field;
        }
        $fields['invoice_vs']['wrapper'] = ['class' => 'form-group col-12'];
        $fields['invoice_text']['wrapper'] = ['class' => 'form-group col-12'];

        return [
            AdminDocumentFormFields::section('selection', __('parties.sections.document_parties')),
            ...AdminDocumentFormFields::parties('customer', $document),
            AdminDocumentFormFields::section('document', __('invoices.sections.document')),
            $fields['invoice_vs'],
            $fields['issue_date'],
            $fields['tax_point_date'],
            $fields['due_in'],
            $fields['payment_method_id'],
            $fields['invoice_ks'],
            $fields['invoice_ss'],
            AdminDocumentFormFields::section('business', __('invoices.fields.supplier')),
            AdminDocumentFormFields::businessPreview('supplier', $document),
            AdminDocumentFormFields::supplierLogo('business', $document),
            AdminDocumentFormFields::section('counterparty', __('invoices.fields.customer')),
            ...AdminDocumentFormFields::counterparty('customer', $countries),
            AdminDocumentFormFields::section(
                'account',
                __('parties.sections.supplier_account'),
                __('parties.hints.document_business_account'),
            ),
            ...AdminDocumentFormFields::accountFields($countries, false),
            AdminDocumentFormFields::section('amounts', __('invoices.sections.amounts')),
            AdminInvoiceItemFields::field($document),
            $fields['payment_amount'],
            $fields['payment_currency'],
            AdminDocumentFormFields::section('status', __('invoices.sections.status')),
            $fields['payment_status_id'],
            AdminDocumentFormFields::section('other', __('invoices.sections.other')),
            $fields['invoice_text'],
            $fields['template'],
        ];
    }
}
