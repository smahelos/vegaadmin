@extends('emails.layout')

@section('content')
    @php
        // Set the locale for translation
        App::setLocale($locale ?? config('app.fallback_locale', 'en'));
    @endphp

    <p class="paragraph">
        {{ __('invoices.reminders.overdue_intro_supplier', ['number' => $invoice->invoice_vs, 'client' => $invoice->client_name, 'days' => $daysOverdue]) }}
    </p>

    <p class="paragraph">
        {{ __('invoices.reminders.due_date_passed', ['date' => $dueDate]) }}
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
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #e8e5ef; color: #e53e3e; font-weight: bold;">
                {{ $invoice->payment_amount }} {{ $invoice->payment_currency }}
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e8e5ef;">
                {{ __('invoices.fields.due_date') }}
            </th>
            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #e8e5ef; color: #e53e3e;">
                {{ $dueDate }} <strong>({{ __('invoices.overdue_days', ['days' => floor($daysOverdue)]) }})</strong>
            </td>
        </tr>
    </table>

    <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <a href="{{ route('frontend.invoice.show', $invoice->id) }}" 
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
