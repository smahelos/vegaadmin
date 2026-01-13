@props([
    'value' => null,
    'formatted' => null,
    'amount' => null,
    'currency' => null,
    'decimals' => 2,
    'locale' => app()->getLocale(),
])
@php
    $loc = $locale ?? app()->getLocale();
    $usesComma = in_array($loc, ['cs','sk','de']);
    $decSep = $usesComma ? ',' : '.';
    $thouSep = $usesComma ? ' ' : ',';
    if ($formatted) {
        $display = $formatted;
    } elseif (is_array($value) && array_key_exists('amount', $value)) {
        $amt = (float)($value['amount'] ?? 0);
        $cur = strtoupper($value['currency'] ?? ($currency ?? ''));
        $display = number_format($amt, $decimals, $decSep, $thouSep).(strlen($cur) ? ' '.$cur : '');
    } elseif ($amount !== null) {
        $cur = strtoupper($currency ?? '');
        $display = number_format((float)$amount, $decimals, $decSep, $thouSep).(strlen($cur) ? ' '.$cur : '');
    } else {
        $display = '';
    }
@endphp
{{ $display }}
