@php
// Explicit language setting for this view
if (isset($locale) && in_array($locale, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
app()->setLocale($locale);
}
@endphp
<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoices.titles.invoice') }} {{ $invoice->invoice_vs }}</title>
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
            text-align: center;
            margin-bottom: 20px;
            background-color: rgb(109, 188, 252);
            color: white;
            padding: 6px;
            border-radius: 5px;
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

        .client-info {
            width: 48%;
            background: #fff;
            border-left: 5px solid #f59e0b;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .supplier-info h3,
        .client-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .supplier-info p,
        .client-info p {
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
        <div class="invoice-title">{{ __('invoices.titles.invoice') }}</div>
        <div class="invoice-number">{{ __('invoices.placeholders.number') }}: {{ $invoice->invoice_vs }}</div>
    </div>

    <div class="parties clearfix">
        <div class="col-left">
            {{-- Supplier information --}}
            <div class="supplier-info">
                <h3>{{ __('invoices.fields.supplier_id') }}</h3>
                <p><strong>{{ $supplier->name ?? $invoice->name ?? '' }}</strong></p>
                <p>{{ $supplier->street ?? $invoice->street ?? '' }}</p>
                <p>{{ $supplier->zip ?? $invoice->zip ?? '' }} {{ $supplier->city ?? $invoice->city ?? '' }}{{
                    ($supplier->country || $invoice->country) ? ',' : '' }} {{ $supplier->country ?? $invoice->country
                    ?? '' }}</p>

                @if(!empty($supplier->ico ?? $invoice->ico))
                    <p>{{ __('invoices.fields.ico') }}: {{ $supplier->ico ?? $invoice->ico }}</p>
                @endif

                @if(!empty($supplier->dic ?? $invoice->dic))
                    <p>{{ __('invoices.fields.dic') }}: {{ $supplier->dic ?? $invoice->dic }}</p>
                @endif
            </div>
        </div>
        <div class="col-right">
            {{-- Client information --}}
            <div class="client-info">
                <h3>{{ __('invoices.fields.client_id') }}</h3>
                <p><strong>{{ $client->name ?? $invoice->client_name ?? '' }}</strong></p>
                <p>{{ $client->street ?? $invoice->client_street ?? '' }}</p>
                <p>{{ $client->zip ?? $invoice->client_zip ?? '' }} {{ $client->city ?? $invoice->client_city ?? '' }}{{
                    ($client->country || $invoice->client_country) ? ',' : '' }} {{ $client->country ??
                    $invoice->client_country ?? '' }}</p>

                @if(!empty($client->ico ?? $invoice->client_ico))
                    <p>{{ __('invoices.fields.ico') }}: {{ $client->ico ?? $invoice->client_ico }}</p>
                @endif

                @if(!empty($client->dic ?? $invoice->client_dic))
                    <p>{{ __('invoices.fields.dic') }}: {{ $client->dic ?? $invoice->client_dic }}</p>
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
                    <span class="info-value">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.tax_point_date') }}:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($invoice->tax_point_date)->format('d.m.Y')
                        }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.due_in') }}:</span>
                    <span class="info-value">{{ $invoice->due_in ?? '0' }} {{ __('invoices.units.days') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label red">{{ __('invoices.fields.due_date') }}:</span>
                    <span class="info-value red">{{
                        \Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->due_in)->format('d.m.Y')
                        }}</span>
                </div>
            </div>

            <div class="col-right">
                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.payment_method') }}:</span>
                    <span class="info-value">
                        @if(isset($paymentMethod) && $paymentMethod)
                            {{ $paymentMethod->slug ? __('payment_methods.' . $paymentMethod->slug) : __('invoices.defaults.payment_method') }}
                        @elseif(isset($invoice->payment_method_id))
                        @php
                            $method = App\Models\PaymentMethod::find($invoice->payment_method_id);
                        @endphp
                            {{ $method ? __('payment_methods.' . $method->slug ?? 'no_method') : __('invoices.defaults.payment_method') }}
                        @else
                            {{ __('invoices.defaults.payment_method') }}
                        @endif
                    </span>
                </div>

                @if($invoice->invoice_vs)
                    <div class="info-row">
                        <span class="info-label">{{ __('invoices.fields.invoice_vs_short') }}:</span>
                        <span class="info-value">{{ $invoice->invoice_vs }}</span>
                    </div>
                @endif

                @if($invoice->invoice_ks)
                    <div class="info-row">
                        <span class="info-label">{{ __('invoices.fields.invoice_ks') }}:</span>
                        <span class="info-value">{{ $invoice->invoice_ks }}</span>
                    </div>
                @endif

                @if($invoice->invoice_ss)
                    <div class="info-row">
                        <span class="info-label">{{ __('invoices.fields.invoice_ss') }}:</span>
                        <span class="info-value">{{ $invoice->invoice_ss }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Invoice amounts -->
    <div class="section">
        <div class="section-title">{{ __('invoices.titles.invoice_items') }}</div>
        @if($invoice->invoiceProductsData && count($invoice->invoiceProductsData) > 0)
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
                    @foreach($invoice->invoiceProductsData as $item)
                    <tr>
                        <td>{{ $item['name'] ?? '-' }}</td>
                        <td class="text-right">{{ $item['quantity'] ?? '-' }}</td>
                        <td class="text-right">{{ $item['unit'] ? __('invoices.units.' . $item['unit']) : 'ks' }}</td>
                        <td class="text-right">
                            @if(isset($item['price']) && $item['price'] > 0)
                            {{ number_format($item['price'], 2, ',', ' ') }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="text-right">
                            @if(isset($item['tax_rate']))
                            {{ $item['tax_rate'] }}%
                            @else
                            0%
                            @endif
                        </td>
                        <td class="text-right">
                            @if(isset($item['total_price']) && $item['total_price'])
                                {{ $item['total_price'] }}
                            @elseif(isset($item['price']) && isset($item['quantity']))
                            @php
                                $tax = isset($item['tax_rate']) ? floatval($item['tax_rate']) : 0;
                                $totatotal_pricelWithTax = floatval($item['price']) * floatval($item['quantity']) * (1 + ($tax / 100));
                                echo number_format($totalWithTax, 2, ',', ' ');
                            @endphp
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="amount-total">
                        <th colspan="5" class="text-right">{{ __('invoices.fields.total') }}</th>
                        <td class="text-right"><strong>{{ number_format($invoice->payment_amount, 2, ',', ' ') }} {{
                                $invoice->payment_currency }}</strong></td>
                    </tr>
                </tfoot>
            </table>

            @if($invoice->invoice_text)
                <div
                    style="margin-top: 10px; padding: 5px 10px 0px 10px; background-color: #f9fafb; border-radius: 5px; border: 1px solid #e5e7eb;">
                    <strong>{{ __('invoices.fields.invoice_note') }}:</strong>
                    <p style="margin-top: 5px;">{{ $invoice->invoice_text }}</p>
                </div>
            @endif
        @endif
    </div>

    <!-- Payment information and QR code -->

    @php
    // Handle bank details for different object types
    $accountNumber = $supplier->account_number ?? ($invoice->account_number ?? '');
    $bankCode = $supplier->bank_code ?? ($invoice->bank_code ?? '');
    $bankName = $supplier->bank_name ?? ($invoice->bank_name ?? '');
    $iban = $supplier->iban ?? ($invoice->iban ?? '');
    $swift = $supplier->swift ?? ($invoice->swift ?? '');
    @endphp

    <div class="clearfix">
        <div class="@if(isset($hasQrCode) && $hasQrCode && !empty($qrCode))col-left @endif">
            <div class="payment-info">
                <div class="payment-info-title">{{ __('invoices.sections.payment_info') }}</div>

                @if(!empty($bankName))
                <div class="info-row">
                    <span class="info-label">{{ __('suppliers.fields.bank_name') }}:</span> <strong>{{ $bankName
                        }}</strong>
                </div>
                @elseif($invoice->supplier && !empty($invoice->supplier->bank_name))
                <div class="info-row">
                    <span class="info-label">{{ __('suppliers.fields.bank_name') }}:</span> <strong>{{
                        $invoice->supplier->bank_name }}</strong>
                </div>
                @endif
                @if(!empty($accountNumber) && !empty($bankCode))
                <div class="info-row">
                    <span class="info-label">{{ __('suppliers.fields.account_number') }}:</span>
                    <strong>{{ $accountNumber }}/{{ $bankCode }}</strong>
                </div>
                @elseif($invoice->supplier && !empty($invoice->supplier->account_number) &&
                !empty($invoice->supplier->bank_code))
                <div class="info-row">
                    <span class="info-label">{{ __('suppliers.fields.account_number') }}:</span>
                    <strong>{{ $invoice->supplier->account_number }}/{{ $invoice->supplier->bank_code }}</strong>
                </div>
                @endif

                @if(!empty($iban))
                <div class="info-row">
                    <span class="info-label">{{ __('suppliers.fields.iban') }}:</span> <strong>{{ $iban }}</strong>
                </div>
                @elseif($invoice->supplier && !empty($invoice->supplier->iban))
                <div class="info-row">
                    <span class="info-label">{{ __('suppliers.fields.iban') }}:</span> <strong>{{
                        $invoice->supplier->iban }}</strong>
                </div>
                @endif

                @if(!empty($swift))
                <div class="info-row">
                    <span class="info-label">{{ __('suppliers.fields.swift') }}:</span> <strong>{{ $swift }}</strong>
                </div>
                @elseif($invoice->supplier && !empty($invoice->supplier->swift))
                <div class="info-row">
                    <span class="info-label">{{ __('suppliers.fields.swift') }}:</span> <strong>{{
                        $invoice->supplier->swift }}</strong>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.amount') }}:</span>
                    <strong>{{ number_format($invoice->payment_amount, 2, ',', ' ') }} {{ $invoice->payment_currency
                        }}</strong>
                </div>

                <div class="info-row">
                    <span class="info-label">{{ __('invoices.fields.invoice_vs_short') }}:</span>
                    <strong>{{ $invoice->invoice_vs }}</strong>
                </div>
            </div>
        </div>

        {{-- QR code for payment --}}
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
        <p>{{ __('invoices.generated_at') }}: {{ now()->format('d.m.Y H:i') }}</p>
        <p>{{ __('invoices.messages.thank_you_pdf') }}</p>
    </div>
</body>

</html>
