@extends('emails.layout')

@section('content')
    @php
        // Set the locale for translation
        App::setLocale($locale ?? config('app.fallback_locale', 'en'));
    @endphp

    <p class="paragraph">
        {{ __('invoices.reminders.due_today_intro_supplier', ['number' => $invoice->invoice_vs, 'client' => $invoice->client_name]) }}
    </p>

    <p class="paragraph">
        {{ __('invoices.reminders.due_date_today') }}
    </p>

    <table style="width: 100%; margin: 15px 0; border-collapse: collapse;">
        <tr>
            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e8e5ef;">
                {{ __('invoices.fields.invoice_vs') }}
            </th>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #e8e5ef;">
                {{ $invoice->invoice_vs }}
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e8e5ef;">
                {{ __('invoices.fields.client_id') }}
            </th>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #e8e5ef;">
                {{ $invoice->client_name }}
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e8e5ef;">
                {{ __('invoices.fields.amount') }}
            </th>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #e8e5ef;">
                {{ $invoice->payment_amount }} {{ $invoice->payment_currency }}
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e8e5ef;">
                {{ __('invoices.fields.due_date') }}
            </th>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #e8e5ef;">
                {{ $dueDate }}
            </td>
        </tr>
    </table>

    <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('frontend.invoice.show', ['id' => $invoice->id, 'locale' => app()->getLocale()]) }}" 
                   class="button" 
                   target="_blank" 
                   rel="noopener">
                    {{ __('invoices.reminders.view_invoice') }}
                </a>
            </td>
        </tr>
    </table>

    <p class="paragraph">
        {{ __('invoices.reminders.thank_you') }}
    </p>
@endsection
