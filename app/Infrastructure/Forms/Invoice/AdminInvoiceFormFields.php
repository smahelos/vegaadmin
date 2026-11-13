<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\Invoice;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Infrastructure\Forms\Party\AdminDocumentFormFields;
use App\Models\Invoice;
use App\Models\PaymentMethod;

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
        foreach ($this->getInvoiceFields() as $field) {
            // The total comes from the lines, which the item editor shows
            if ($field['name'] === 'invoice_logo' || $field['name'] === 'payment_amount') {
                continue;
            }
            $field['wrapper'] = ['class' => 'form-group col-12 col-md-6'];
            if ($document !== null) {
                unset($field['default']);
            }
            switch ($field['type']) {
                case 'date':
                    $field['type'] = 'datepicker';
                    break;
                case 'select_from_array':
                    $field['type'] = 'select2_from_array';
                    if ($field['name'] === 'status' && $document !== null) {
                        $field['value'] = $document->paymentStatus?->slug;
                    }
                    $current = $document?->getAttribute($field['name']);
                    if (filled($current) && !array_key_exists($current, $field['options'])) {
                        $field['options'][$current] = $field['name'] === 'due_in' ? $current . ' ' . __('invoices.units.days') : $current;
                    }
                    break;
                case 'select':
                    $field['type'] = 'select2';
                    unset($field['options']);
                    if ($document === null) {
                        $query = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value);
                        $field['default'] = $query->value('id');
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
        $parties = AdminDocumentFormFields::parties('customer', $document);

        return [
            AdminDocumentFormFields::section(
                'selection',
                __('parties.sections.document_parties'),
            ),
            ...$parties,
            AdminDocumentFormFields::section(
                'document',
                __('invoices.sections.document'),
            ),
            $fields['invoice_vs'],
            $fields['issue_date'],
            $fields['tax_point_date'],
            $fields['due_in'],
            $fields['payment_method_id'],
            $fields['invoice_ks'],
            $fields['invoice_ss'],
            AdminDocumentFormFields::section(
                'business',
                __('invoices.fields.supplier'),
            ),
            AdminDocumentFormFields::businessPreview('supplier', $document),
            AdminDocumentFormFields::supplierLogo('business', $document),
            AdminDocumentFormFields::section(
                'counterparty',
                __('invoices.fields.customer'),
            ),
            ...AdminDocumentFormFields::counterparty('customer', $countries),
            AdminDocumentFormFields::section(
                'account',
                __('parties.sections.supplier_account'),
                __('parties.hints.document_business_account'),
            ),
            ...AdminDocumentFormFields::accountFields($countries, false),
            AdminDocumentFormFields::section(
                'amounts',
                __('invoices.sections.amounts'),
            ),
            AdminInvoiceItemFields::field($document),
            $fields['payment_currency'],
            AdminDocumentFormFields::section(
                'status',
                __('invoices.sections.status'),
            ),
            $fields['status'],
            AdminDocumentFormFields::section(
                'other',
                __('invoices.sections.other'),
            ),
            $fields['invoice_text'],
            $fields['template'],
        ];
    }
}
