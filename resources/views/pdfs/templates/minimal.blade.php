<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoices.titles.invoice') }} {{ $invoice->number }}</title>
    <style>
        @page {
            margin: 15mm 10mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #2d3748;
            margin: 0;
            padding: 0;
        }

        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #5c5e60;
        }

        .header-logo-container {
            max-height: 40px;
            float: left;
            text-align: right;
            width: 30%;
            object-fit: contain;
        }

        .header-logo {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .header-content {
            float: left;
            text-align: left;
            width: 70%;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #2d3748;
        }

        .invoice-number {
            font-size: 12px;
            margin-bottom: 0;
            color: #718096;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 10px;
            color: #2d3748;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .parties {
            display: flex;
            margin-bottom: 25px;
            justify-content: space-between;
        }

        .supplier-info {
            width: 97%;
            margin-right: 3%;
        }

        .customer-info {
            width: 97%;
            margin-left: 3%;
        }

        .supplier-info h3,
        .customer-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2d3748;
        }

        .supplier-info p,
        .customer-info p {
            margin: 3px 0;
            line-height: 1.4;
        }

        .row {
            display: flex;
            margin-bottom: 3px;
        }

        .col {
            flex: 1;
        }

        .col-left {
            width: 48%;
            float: left;
        }

        .col-right {
            width: 48%;
            float: right;
        }

        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        .info-row {
            margin-bottom: 3px;
            line-height: 1.4;
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            font-weight: normal;
            color: #718096;
            min-width: 120px;
        }

        .info-value {
            font-weight: bold;
            color: #2d3748;
            text-align: right;
        }

        .payment-info {
            padding-right: 15px;
        }

        .payment-info-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2d3748;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .qr-code {
            text-align: left;
            padding-right: 15px;
        }

        .qr-code h3 {
            margin-top: 0;
            color: #2d3748;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .qr-code img {
            max-width: 100px;
            max-height: 100px;
            border: 1px solid #e2e8f0;
            padding: 3px;
            background: white;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #718096;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            background: white;
        }

        table th {
            background-color: #ebf1f6;
            color: #2d3748;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table th,
        table td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
        }

        table tr:nth-child(even) {
            background: #f7fafc;
        }

        .text-right {
            text-align: right;
        }

        .amount-total {
            background-color: #ebf1f6;
            color: #2d3748;
            font-weight: bold;
        }

        .payment-details {
            background-color: #fed7d7;
            color: #c53030;
            padding: 10px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #fc8181;
        }

        .red {
            color: #c53030;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-content">
            <div class="invoice-title">{{ __('invoices.titles.invoice') }}</div>
            <div class="invoice-number">{{ __('invoices.placeholders.number') }}: {{ $invoice->number }}</div>
        </div>

        @if($logoPath)
        <div class="header-logo-container">
            <img src="{{ $logoPath }}" alt="{{ __('invoices.labels.company_logo') }}"
                class="header-logo">
        </div>
        @endif

        @if(!$logoPath)
        <div style="width: 100px;"></div>
        @endif
        <div class="clearfix"></div>

    </div>
    <div class="parties clearfix">
        <div class="col-left">
            <div class="supplier-info">
                <div class="section-title">{{ __('invoices.fields.supplier') }}</div>
                <p><strong>{{ $supplier->name ?? '' }}</strong></p>
                <p>{{ $supplier->street ?? '' }}</p>
                <p>{{ $supplier->postalCode ?? '' }} {{ $supplier->city ?? '' }}{{
                    ($supplier->country) ? ',' : '' }} {{ $supplier->country
                    ?? '' }}</p>

                @if ($supplier->email)
                    <p>{{ $supplier->email }}</p>
                @endif
                @if ($supplier->phone)
                    <p>{{ $supplier->phone }}</p>
                @endif
                @if(!empty($supplier->companyNumber))
                <p>{{ __('parties.fields.company_number') }}: {{ $supplier->companyNumber }}</p>
                @endif

                @if(!empty($supplier->vatNumber))
                <p>{{ __('parties.fields.vat_number') }}: {{ $supplier->vatNumber }}</p>
                @endif
            </div>
        </div>
        <div class="col-right">
            <div class="customer-info">
                <div class="section-title">{{ __('invoices.fields.customer') }}</div>
                <p><strong>{{ $customer->name ?? '' }}</strong></p>
                <p>{{ $customer->street ?? '' }}</p>
                <p>{{ $customer->postalCode ?? '' }} {{ $customer->city ?? '' }}{{
                    ($customer->country) ? ',' : '' }} {{ $customer->country ?? '' }}</p>

                @if ($customer->email)
                    <p>{{ $customer->email }}</p>
                @endif
                @if ($customer->phone)
                    <p>{{ $customer->phone }}</p>
                @endif
                @if(!empty($customer->companyNumber))
                <p>{{ __('parties.fields.company_number') }}: {{ $customer->companyNumber }}</p>
                @endif

                @if(!empty($customer->vatNumber))
                <p>{{ __('parties.fields.vat_number') }}: {{ $customer->vatNumber }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="section clearfix">
        <div class="section-title">{{ __('invoices.sections.invoice_details') }}</div>
        <div class="clearfix">
            <div class="col-left">
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.issue_date') }}:</span>
                    <span class="info-value">{{ $issueDateFormatted }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.tax_point_date') }}:</span>
                    <span class="info-value">{{ $taxPointDateFormatted }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.due_in') }}:</span>
                    <span class="info-value">{{ $dueDays ?? '0' }} {{ __('invoices.units.days') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label red">{{ __('invoices.fields.due_date') }}:</span>
                    <span class="info-value red">{{ $dueDateFormatted }}</span>
                </div>
            </div>

            <div class="col-right">
                @if($paymentMethod)
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.payment_method') }}:</span>
                    <span class="info-value">{{ __('payment_methods.methods.' . $paymentMethod->value) }}</span>
                </div>
                @endif

                @if($invoice->number)
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.invoice_vs') }}:</span>
                    <span class="info-value">{{ $invoice->number }}</span>
                </div>
                @endif

                @if($invoice->constantSymbol)
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.invoice_ks') }}:</span>
                    <span class="info-value">{{ $invoice->constantSymbol }}</span>
                </div>
                @endif

                @if($invoice->specificSymbol)
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.invoice_ss') }}:</span>
                    <span class="info-value">{{ $invoice->specificSymbol }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Invoice amounts -->
    <div class="section">
        <div class="section-title">{{ __('invoices.titles.invoice_items') }}</div>
        @if($invoiceProductsFormatted && count($invoiceProductsFormatted) > 0)
        <!-- Structured data from JSON -->
        <table>
            <thead>
                <tr>
                    <th>{{ __('invoices.placeholders.item_name') }}</th>
                    <th>{{ __('invoices.placeholders.item_quantity') }}</th>
                    <th>{{ __('invoices.placeholders.item_unit') }}</th>
                    <th>{{ __('invoices.placeholders.item_price') }}</th>
                    <th>{{ __('invoices.placeholders.item_tax') }}</th>
                    <th class="text-right">{{ __('invoices.placeholders.item_price_complete') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoiceProductsFormatted as $item)
                <tr>
                    <td>{{ $item['name'] ?? __('invoices.placeholders.unnamed_product') }}</td>
                    <td>{{ $item['quantity_formatted'] }}</td>
                    <td>{{ __('invoices.units.' . $item['unit']) }}</td>
                    <td>{{ $item['price_formatted'] }}</td>
                    <td>{{ $item['tax_rate_formatted'] }}%</td>
                    <td class="text-right">{{ $item['total_price_formatted'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">{{ __('invoices.labels.total_without_tax') }}</td>
                    <td class="text-right"><strong>{{ $subtotalFormatted }}</strong></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">{{ __('invoices.labels.total_tax') }}</td>
                    <td class="text-right"><strong>{{ $taxFormatted }}</strong></td>
                </tr>
                <tr class="amount-total">
                    <td colspan="5"><strong>{{ __('invoices.fields.total') }}</strong></td>
                    <td class="text-right"><strong>{{ $paymentAmountFormatted }}</strong></td>
                </tr>
            </tfoot>
        </table>

        @if($invoice->notes)
        <div style="margin-top: 10px; padding: 10px; border: 1px solid #e2e8f0;">
            <strong>{{ __('invoices.fields.invoice_note') }}:</strong>
            <p style="margin-top: 5px;">{{ $invoice->notes }}</p>
        </div>
        @endif
        @endif
    </div>

    <!-- Payment information and QR code -->
    <div class="clearfix">
        <div class="@if(isset($hasQrCode) && $hasQrCode && !empty($qrCode))col-left @endif">
            <div class="payment-info">
                <div class="section-title">{{ __('invoices.sections.payment_info') }}</div>

                @if(!empty($bankName))
                <div class="info-row">
                    <span class="info-label">{{ __('parties.bank_accounts.fields.bank_name') }}:</span>
                    <span class="info-value">{{ $bankName }}</span>
                </div>
                @endif

                @if(!empty($accountNumber) && !empty($bankCode))
                <div class="info-row">
                    <span class="info-label">{{ __('parties.bank_accounts.fields.number') }}:</span>
                    <span class="info-value">{{ $accountNumber }}/{{ $bankCode }}</span>
                </div>
                @endif

                @if(!empty($iban))
                <div class="info-row">
                    <span class="info-label">{{ __('parties.bank_accounts.fields.iban') }}:</span>
                    <span class="info-value">{{ $iban }}</span>
                </div>
                @endif

                @if(!empty($swift))
                <div class="info-row">
                    <span class="info-label">{{ __('parties.bank_accounts.fields.swift') }}:</span>
                    <span class="info-value">{{ $swift }}</span>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.payment_amount') }}:</span>
                    <span class="info-value">{{ $paymentAmountFormatted }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.invoice_vs') }}:</span>
                    <span class="info-value">{{ $invoice->number }}</span>
                </div>
            </div>
        </div>
        @if(isset($hasQrCode) && $hasQrCode && !empty($qrCode))
        <div class="col-right">
            <div class="qr-code">
                <div class="section-title">{{ __('invoices.sections.qr_payment') }}</div>
                <img src="{{ $qrCode }}" alt="{{ __('invoices.sections.qr_payment') }}">
            </div>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>{{ __('invoices.generated_at') }}: {{ $generatedAt }}</p>
        <p>{{ __('invoices.messages.thank_you_pdf') }}</p>
    </div>
</body>

</html>
