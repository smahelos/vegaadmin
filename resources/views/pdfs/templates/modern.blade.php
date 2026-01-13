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
            line-height: 1.4;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .container {
            background: white;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 0px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .invoice-number {
            font-size: 12px;
            margin-bottom: 0;
            color: #4975b7;
        }

        .section {
            margin-bottom: 15px;
            background: linear-gradient(145deg, #f8fafc, #e2e8f0);
            padding: 5px 10px 10px 10px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 2px solid #667eea;
        }

        .section-red {
            border: 2px solid #d34356;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-red .section-title {
            color: #d34356;
        }

        .parties {
            display: flex;
            margin-bottom: 15px;
            justify-content: space-between;
        }

        .supplier-info {
            width: calc(99% - 24px);
            /* Adjusted width to fit within the container */
            background: linear-gradient(145deg, #ffffff, #f1f5f9);
            border: 2px solid #10b981;
            padding: 5px 10px 10px 10px;
            border-radius: 8px;
            margin-right: 1%;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.1);
        }

        .client-info {
            width: calc(99% - 24px);
            /* Adjusted width to fit within the container */
            background: linear-gradient(145deg, #ffffff, #f1f5f9);
            border: 2px solid #f59e0b;
            padding: 5px 10px 10px 10px;
            border-radius: 8px;
            margin-left: 1%;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.1);
        }

        .supplier-info h3,
        .client-info h3 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1a1a1a;
        }

        .supplier-info h3 {
            color: #10b981;
        }

        .client-info h3 {
            color: #f59e0b;
        }

        .supplier-info p,
        .client-info p {
            margin: 3px 0;
            line-height: 1.3;
        }

        .row {
            display: flex;
            margin-bottom: 8px;
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
            margin-bottom: 2px;
            line-height: 1.4;
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            font-weight: bold;
            color: #64748b;
            min-width: 150px;
        }

        .info-value {
            font-weight: bold;
            color: #1a1a1a;
            text-align: right;
        }

        .payment-info {
            border: 2px solid #3b82f6;
            padding: 5px 10px 10px 10px;
            background: #dbeafe;
            border-radius: 8px;
        }

        .payment-info-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #1e40af;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .qr-code {
            text-align: left;
            background: #f1f5f9;
            padding: 5px 10px 10px 10px;
            border-radius: 8px;
            border: 2px solid #dbeafe;
        }

        .qr-code h3 {
            margin-top: 0;
            color: #1e40af;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .qr-code img {
            max-width: 100px;
            max-height: 100px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 10px 0;
            background: white;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        table th {
            background: #ebf1f6;
            color: #1a1a1a;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table th,
        table td {
            border: none;
            padding: 3px 5px 3px 5px;
        }

        table tr:nth-child(even) {
            background: rgba(102, 126, 234, 0.05);
        }

        .text-right {
            text-align: right;
        }

        .amount-total {
            background: #ebf1f6;
            color: #1a1a1a;
            font-weight: bold;
        }

        .payment-details {
            background: linear-gradient(145deg, #fee2e2, #fecaca);
            color: #b91c1c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: bold;
            text-align: center;
            border: 2px solid #fca5a5;
        }

        .red {
            color: #b91c1c;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @php
            $logoPath = $invoice->invoice_logo ?? $invoice->supplier_logo ?? $supplier->supplier_logo ?? '';
            $hasLogo = !empty($logoPath) && file_exists(storage_path('app/public/' . $logoPath));
            @endphp

            <div class="header-content">
                <div class="invoice-title">{{ __('invoices.titles.invoice') }}</div>
                <div class="invoice-number">{{ __('invoices.placeholders.number') }}: {{ $invoice->invoice_vs }}</div>
            </div>

            @if($hasLogo)
            <div class="header-logo-container">
                <img src="{{ storage_path('app/public/' . $logoPath) }}" alt="{{ __('invoices.labels.company_logo') }}"
                    class="header-logo">
            </div>
            @endif

            @if(!$hasLogo)
            {{-- Empty div to maintain flexbox structure when no logo --}}
            <div style="width: 140px;"></div>
            @endif
            <div class="clearfix"></div>
        </div>

        <div class="parties clearfix">
            <div class="col-left">
                {{-- Supplier information --}}
                <div class="supplier-info">
                    <h3>{{ __('invoices.fields.supplier_id') }}</h3>
                    <p><strong>{{ $supplier->name ?? $invoice->name ?? '' }}</strong></p>
                    <p>{{ $supplier->street ?? $invoice->street ?? '' }}</p>
                    <p>{{ $supplier->zip ?? $invoice->zip ?? '' }} {{ $supplier->city ?? $invoice->city ?? '' }}{{
                        ($supplier->country || $invoice->country) ? ',' : '' }} {{ $supplier->country ??
                        $invoice->country
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
                    <p>{{ $client->zip ?? $invoice->client_zip ?? '' }} {{ $client->city ?? $invoice->client_city ?? ''
                        }}{{
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
                        <span class="info-value">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y')
                            }}</span>
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
                        <span class="info-value red">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y')
                            }}</span>
                    </div>
                </div>

                <div class="col-right">
                    <div class="info-row">
                        <span class="info-label">{{ __('invoices.fields.payment_method') }}:</span>
                        <span class="info-value">
                            {{ $paymentMethod->name ?? __('invoices.labels.bank_transfer') }}
                        </span>
                    </div>

                    @if($invoice->invoice_vs)
                    <div class="info-row">
                        <span class="info-label">{{ __('invoices.fields.invoice_vs') }}:</span>
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
        <div class="section section-red">
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
                    @foreach(($invoiceProductsFormatted ?? $invoice->invoiceProductsData) as $item)
                    <tr>
                        <td>{{ $item['name'] ?? __('invoices.placeholders.unnamed_product') }}</td>
                        <td>{{ isset($item['quantity']) ? number_format($item['quantity'], 2, ',', ' ') : '0' }}</td>
                        <td>{{ $item['unit'] ?? __('invoices.units.pieces') }}</td>
                        <td>{{ $item['price_formatted'] ?? (number_format($item['price'] ?? 0, 2, ',', ' ') . ' ' .
                            ($item['currency'] ?? $invoice->payment_currency)) }}</td>
                        <td>{{ isset($item['tax_rate']) ? number_format($item['tax_rate'], 0) : '0' }}%</td>
                        <td class="text-right">{{ $item['total_price_formatted'] ?? (number_format($item['total_price']
                            ?? 0, 2, ',', ' ') . ' ' . ($item['currency'] ?? $invoice->payment_currency)) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right">{{ __('invoices.labels.total_without_tax') }}</td>
                        <td class="text-right"><strong>
                                <x-money :formatted="$subtotalFormatted ?? null" :value="$subtotal ?? null"
                                    :amount="isset($subtotal)?($subtotal['amount']??0):0"
                                    :currency="$subtotal['currency'] ?? ($invoice->payment_currency ?? 'CZK')" />
                            </strong></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-right">{{ __('invoices.labels.total_tax') }}</td>
                        <td class="text-right"><strong>
                                <x-money :formatted="$taxFormatted ?? null" :value="$totalTax ?? null"
                                    :amount="isset($totalTax)?($totalTax['amount']??0):0"
                                    :currency="$totalTax['currency'] ?? ($invoice->payment_currency ?? 'CZK')" />
                            </strong></td>
                    </tr>
                    <tr class="amount-total">
                        <td colspan="5"><strong>{{ __('invoices.fields.total') }}</strong></td>
                        <td class="text-right"><strong>
                                <x-money :formatted="$paymentAmountFormatted ?? null" :value="$paymentAmount ?? null"
                                    :amount="$invoice->payment_amount" :currency="$invoice->payment_currency" />
                            </strong></td>
                    </tr>
                </tfoot>
            </table>

            @if($invoice->invoice_text)
            <div
                style="margin-top: 15px; padding: 5px; background: #f1f5f9; border-radius: 8px; border: 2px solid #e2e8f0;">
                <strong>{{ __('invoices.fields.invoice_note') }}:</strong>
                <p style="margin-top: 8px;">{{ $invoice->invoice_text }}</p>
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
                        <span class="info-label">{{ __('suppliers.fields.bank_name') }}:</span>
                        <span class="info-value">{{ $bankName }}</span>
                    </div>
                    @elseif($supplier && !empty($supplier->bank_name))
                    <div class="info-row">
                        <span class="info-label">{{ __('suppliers.fields.bank_name') }}:</span>
                        <span class="info-value">{{ $supplier->bank_name }}</span>
                    </div>
                    @endif

                    @if(!empty($accountNumber) && !empty($bankCode))
                    <div class="info-row">
                        <span class="info-label">{{ __('suppliers.fields.account_number') }}:</span>
                        <span class="info-value">{{ $accountNumber }}/{{ $bankCode }}</span>
                    </div>
                    @elseif($supplier && !empty($supplier->account_number) &&
                    !empty($supplier->bank_code))
                    <div class="info-row">
                        <span class="info-label">{{ __('suppliers.fields.account_number') }}:</span>
                        <span class="info-value">{{ $supplier->account_number }}/{{
                            $supplier->bank_code }}</span>
                    </div>
                    @endif

                    @if(!empty($iban))
                    <div class="info-row">
                        <span class="info-label">{{ __('suppliers.fields.iban') }}:</span>
                        <span class="info-value">{{ $iban }}</span>
                    </div>
                    @elseif($supplier && !empty($supplier->iban))
                    <div class="info-row">
                        <span class="info-label">{{ __('suppliers.fields.iban') }}:</span>
                        <span class="info-value">{{ $supplier->iban }}</span>
                    </div>
                    @endif

                    @if(!empty($swift))
                    <div class="info-row">
                        <span class="info-label">{{ __('suppliers.fields.swift') }}:</span>
                        <span class="info-value">{{ $swift }}</span>
                    </div>
                    @elseif($supplier && !empty($supplier->swift))
                    <div class="info-row">
                        <span class="info-label">{{ __('suppliers.fields.swift') }}:</span>
                        <span class="info-value">{{ $supplier->swift }}</span>
                    </div>
                    @endif

                    <div class="info-row">
                        <span class="info-label">{{ __('invoices.fields.payment_amount') }}:</span>
                        <span class="info-value">{{ $paymentAmountFormatted ?? (isset($paymentAmount) ?
                            (number_format((float)($paymentAmount['amount'] ?? ($invoice->payment_amount ?? 0)), 2, ',',
                            ' ') . ' ' . ($paymentAmount['currency'] ?? ($invoice->payment_currency ?? 'CZK'))) :
                            (number_format($invoice->payment_amount, 2, ',', ' ') . ' ' . $invoice->payment_currency))
                            }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">{{ __('invoices.fields.invoice_vs') }}:</span>
                        <span class="info-value">{{ $invoice->invoice_vs }}</span>
                    </div>
                </div>
            </div>

            {{-- QR code for payment --}}
            @if(isset($hasQrCode) && $hasQrCode && !empty($qrCode))
            <div class="col-right">
                <div class="qr-code">
                    <div class="payment-info-title">{{ __('invoices.sections.qr_payment') }}</div>
                    <img src="{{ $qrCode }}" alt="{{ __('invoices.sections.qr_payment') }}">
                </div>
            </div>
            @endif
        </div>

        <div class="footer">
            <p>{{ __('invoices.generated_at') }}: {{ now()->format('d.m.Y H:i') }}</p>
            <p>{{ __('invoices.messages.thank_you_pdf') }}</p>
        </div>
    </div>

    {{-- Replace occurrences of raw total / amount if present later --}}
    {{-- Fallback to formatted variable when available --}}
    @php $formattedTotal = $paymentAmountFormatted ?? (number_format($invoice->payment_amount, 2, ',', ' ') . ' ' .
    $invoice->payment_currency); @endphp
    {{-- Example injection point if total cell present: --}}
    {{-- <span class="some-total-class">{{ $formattedTotal }}</span> --}}
</body>

</html>
