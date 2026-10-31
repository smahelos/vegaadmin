<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoices.titles.invoice') }} {{ $invoice->number }}</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.2;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            margin-bottom: 15px;
            background-color: rgb(109, 188, 252);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
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
            max-height: 60px;
            max-width: 140px;
            object-fit: contain;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 5px;
        }

        .header-content {
            float: left;
            text-align: left;
            width: 70%;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .invoice-number {
            font-size: 14px;
            margin-bottom: 0;
        }

        .section {
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 10px !important;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 5px;
            color: #1e40af;
        }

        .parties {
            display: flex;
            margin-bottom: 20px;
            justify-content: space-between;
        }

        .supplier-info {
            width: 48%;
            background: #fff;
            border-left: 5px solid #10b981;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .customer-info {
            width: 48%;
            background: #fff;
            border-left: 5px solid #f59e0b;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .supplier-info h3,
        .customer-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .supplier-info p,
        .customer-info p {
            margin: 5px 0;
        }

        .row {
            display: flex;
            margin-bottom: 5px;
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
        }

        .info-label {
            font-weight: bold;
            width: 200px;
            color: #6b7280;
        }

        .info-value {
            font-weight: bold;
            color: rgb(29, 29, 29);
        }

        .payment-info {
            border: 1px solid #d1d5db;
            padding: 10px;
            background-color: #f9fafb;
            border-radius: 5px;
        }

        .payment-info-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #1e40af;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .qrcode-title {
            border-bottom: 1px solid #ffffff;
        }

        .qr-code {
            text-align: center;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            background-color: #dbeafe;
        }

        .qr-code h3 {
            margin-top: 0;
            color: #1e40af;
            font-size: 14px;
        }

        .qr-code img {
            max-width: 120px;
            max-height: 120px;
            border: 1px solid #e5e7eb;
            padding: 5px;
            background: white;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 5px 0;
            background: white;
        }

        table th {
            background-color: #e5e7eb;
            text-align: left;
            color: #1f2937;
        }

        table th,
        table td {
            border: 1px solid #d1d5db;
            padding: 5px;
        }

        .text-right {
            text-align: right;
        }

        .bank-info {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 5px solid #3b82f6;
        }

        .bank-info h3 {
            margin-top: 0;
            color: #1e40af;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .bank-info p {
            margin: 5px 0;
        }

        .amount-total {
            background-color: #dbeafe;
            font-weight: bold;
        }

        .payment-details {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }

        .red {
            color: #b91c1c;
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
        <div style="width: 120px;"></div>
        @endif
        <div class="clearfix"></div>
    </div>

    <div class="parties clearfix">
        <div class="col-left">
            <div class="supplier-info">
                <h3>{{ __('invoices.fields.supplier') }}</h3>
                <p><strong>{{ $supplier->name ?? '' }}</strong></p>
                <p>{{ $supplier->street ?? '' }}</p>
                <p>{{ $supplier->postalCode ?? '' }} {{ $supplier->city ?? '' }}{{
                    ($supplier->country) ? ',' : '' }} {{ $supplier->country ?? '' }}</p>

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
                <h3>{{ __('invoices.fields.customer') }}</h3>
                <p><strong>{{ $customer->name ?? '' }}</strong></p>
                <p>{{ $customer->street ?? '' }}</p>
                <p>{{ $customer->postalCode ?? '' }} {{ $customer->city ?? '' }}{{
                    $customer->country ? ',' : '' }} {{ $customer->country ?? '' }}</p>

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
                    <span class="info-label">{{ __('invoices.fields.invoice_vs_short') }}:</span>
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
                    <th class="text-right">{{ __('invoices.placeholders.item_quantity') }}</th>
                    <th class="text-right">{{ __('invoices.placeholders.item_unit') }}</th>
                    <th class="text-right">{{ __('invoices.placeholders.item_price') }}</th>
                    <th class="text-right">{{ __('invoices.placeholders.item_tax') }}</th>
                    <th class="text-right">{{ __('invoices.placeholders.item_price_complete') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoiceProductsFormatted as $item)
                <tr>
                    <td>{{ $item['name'] ?? '-' }}</td>
                    <td class="text-right">{{ $item['quantity_formatted'] }}</td>
                    <td class="text-right">{{ __('invoices.units.' . $item['unit']) }}</td>
                    <td class="text-right">{{ $item['price_formatted'] }}</td>
                    <td class="text-right">{{ $item['tax_rate_formatted'] }}%</td>
                    <td class="text-right">{{ $item['total_price_formatted'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-right">{{ __('invoices.labels.total_without_tax') }}</th>
                    <td class="text-right"><strong>
                            {{ $subtotalFormatted }}
                        </strong></td>
                </tr>
                <tr>
                    <th colspan="5" class="text-right">{{ __('invoices.labels.total_tax') }}</th>
                    <td class="text-right"><strong>{{ $taxFormatted }}</strong></td>
                </tr>
                <tr class="amount-total">
                    <th colspan="5" class="text-right">{{ __('invoices.fields.total') }}</th>
                    <td class="text-right"><strong>{{ $paymentAmountFormatted }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @if($invoice->notes)
        <div
            style="margin-top: 10px; padding: 5px 10px 0px 10px; background-color: #f9fafb; border-radius: 5px; border: 1px solid #e5e7eb;">
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
                <div class="payment-info-title">{{ __('invoices.sections.payment_info') }}</div>

                @if(!empty($bankName))
                <div class="info-row">
                    <span class="info-label">{{ __('parties.bank_accounts.fields.bank_name') }}:</span> <strong>{{ $bankName
                        }}</strong>
                </div>
                @endif
                @if(!empty($accountNumber) && !empty($bankCode))
                <div class="info-row">
                    <span class="info-label">{{ __('parties.bank_accounts.fields.number') }}:</span>
                    <strong>{{ $accountNumber }}/{{ $bankCode }}</strong>
                </div>
                @endif

                @if(!empty($iban))
                <div class="info-row">
                    <span class="info-label">{{ __('parties.bank_accounts.fields.iban') }}:</span> <strong>{{ $iban }}</strong>
                </div>
                @endif

                @if(!empty($swift))
                <div class="info-row">
                    <span class="info-label">{{ __('parties.bank_accounts.fields.swift') }}:</span> <strong>{{ $swift }}</strong>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.amount') }}:</span>
                    <strong>{{ $paymentAmountFormatted }}</strong>
                </div>

                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.invoice_vs_short') }}:</span>
                    <strong>{{ $invoice->number }}</strong>
                </div>
            </div>
        </div>
        @if(isset($hasQrCode) && $hasQrCode && !empty($qrCode))
        <div class="col-right">
            <div class="qr-code">
                <div class="payment-info-title qrcode-title">{{ __('invoices.sections.qr_payment') }}</div>
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
