@php
// Explicit language setting for this view
if (isset($locale) && in_array($locale, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
app()->setLocale($locale);
}
$formattedTotal = $paymentAmountFormatted ?? (isset($invoice->payment_amount) ? number_format($invoice->payment_amount,
2, ',', ' ') . ' ' . ($invoice->payment_currency ?? 'CZK') : '');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoices.titles.invoice') }} {{ $invoice->invoice_vs }}</title>
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

        .client-info {
            width: 97%;
            margin-left: 3%;
        }

        .supplier-info h3,
        .client-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2d3748;
        }

        .supplier-info p,
        .client-info p {
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
        <div style="width: 100px;"></div>
        @endif
        <div class="clearfix"></div>

    </div>
    <div class="parties clearfix">
        <div class="col-left">
            {{-- Supplier information --}}
            <div class="supplier-info">
                <div class="section-title">{{ __('invoices.fields.supplier_id') }}</div>
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
                <div class="section-title">{{ __('invoices.fields.client_id') }}</div>
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
                    <span class="info-value red">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}</span>
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
                @foreach(($invoiceProductsFormatted ?? $invoice->invoiceProductsData) as $item)
                <tr>
                    <td>{{ $item['name'] ?? __('invoices.placeholders.unnamed_product') }}</td>
                    <td>{{ isset($item['quantity']) ? number_format($item['quantity'], 2, ',', ' ') : '0' }}</td>
                    <td>{{ $item['unit'] ?? __('invoices.units.pieces') }}</td>
                    <td>{{ $item['price_formatted'] ?? (number_format($item['price'] ?? 0, 2, ',', ' ') . ' ' .
                        ($item['currency'] ?? $invoice->payment_currency)) }}</td>
                    <td>{{ isset($item['tax_rate']) ? number_format($item['tax_rate'], 0) : '0' }}%</td>
                    <td class="text-right">{{ $item['total_price_formatted'] ?? (number_format($item['total_price'] ??
                        0, 2, ',', ' ') . ' ' . ($item['currency'] ?? $invoice->payment_currency)) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">{{ __('invoices.labels.total_without_tax') }}</td>
                    <td class="text-right"><strong><x-money :formatted="$subtotalFormatted ?? null" :value="$subtotal ?? null" :amount="isset($subtotal)?($subtotal['amount']??0):0" :currency="$subtotal['currency'] ?? ($invoice->payment_currency ?? 'CZK')" /></strong></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">{{ __('invoices.labels.total_tax') }}</td>
                    <td class="text-right"><strong><x-money :formatted="$taxFormatted ?? null" :value="$totalTax ?? null" :amount="isset($totalTax)?($totalTax['amount']??0):0" :currency="$totalTax['currency'] ?? ($invoice->payment_currency ?? 'CZK')" /></strong></td>
                </tr>
                <tr class="amount-total">
                    <td colspan="5"><strong>{{ __('invoices.fields.total') }}</strong></td>
                    <td class="text-right"><strong><x-money :formatted="$paymentAmountFormatted ?? null" :value="$paymentAmount ?? null" :amount="$invoice->payment_amount" :currency="$invoice->payment_currency" /></strong></td>
                </tr>
            </tfoot>
        </table>

        @if($invoice->invoice_text)
        <div style="margin-top: 10px; padding: 10px; border: 1px solid #e2e8f0;">
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
                <div class="section-title">{{ __('invoices.sections.payment_info') }}</div>

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
                @elseif($supplier && !empty($supplier->account_number) && !empty($supplier->bank_code))
                <div class="info-row">
                    <span class="info-label">{{ __('suppliers.fields.account_number') }}:</span>
                    <span class="info-value">{{ $supplier->account_number }}/{{ $supplier->bank_code }}</span>
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
                    <span class="info-value">{{ $paymentAmountFormatted ?? (number_format($invoice->payment_amount, 2,
                        ',', ' ') . ' ' . $invoice->payment_currency) }}</span>
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
                <div class="section-title">{{ __('invoices.sections.qr_payment') }}</div>
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
