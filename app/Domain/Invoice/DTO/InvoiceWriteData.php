<?php

namespace App\Domain\Invoice\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Immutable write data for Invoice mutations.
 * Null means "do not change" for updates.
 */
class InvoiceWriteData
{
    public function __construct(
        // Required parameters first (no default values)
        public readonly ?string $payment_reference,
        public readonly ?string $constant_code,
        public readonly ?string $tax_point_date,
        
        // Optional parameters with default values
        public readonly ?int $client_id = null,
        public readonly ?int $supplier_id = null,
        public readonly ?string $number = null,
        public readonly ?string $issue_date = null,
        public readonly ?int $due_in = null,
        public readonly ?string $currency = null,
        public readonly ?string $template = null,
        public readonly ?string $note = null,
        //public readonly ?array $meta = null,
        public readonly ?int $user_id = null,
        // Totals are optional on write; when provided they should be Money VO
        public readonly ?Money $subtotal = null,
        public readonly ?Money $total_tax = null,
        public readonly ?Money $total_amount = null,
        public readonly ?int $payment_method_id = null,
        public readonly ?string $invoice_text = null,
        public readonly array $items = [],
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
        public readonly ?string $logo = null,
        public readonly ?string $status = null,
    ) {}

    public static function fromArray(array $data): self
    {
        // Normalize currency from multiple accepted shapes
        $currency = $data['currency']
            ?? $data['payment_currency']
            ?? null;
        if (is_string($currency)) {
            $currency = strtoupper($currency);
        }

        // Helper to normalize string/float amount into canonical 2-decimal string
        $normalizeAmount = function (string|float|int $amount): string {
            return is_string($amount)
                ? (function (string $s) {
                    $s = str_replace([' ', ','], ['', '.'], trim($s));
                    if (!preg_match('/^\d+(?:\.\d+)?$/', $s)) {
                        return number_format((float) $s, 2, '.', '');
                    }
                    if (str_contains($s, '.')) {
                        [$i, $f] = explode('.', $s, 2);
                        $f = substr($f, 0, 2);
                        return $i . '.' . str_pad($f, 2, '0', STR_PAD_RIGHT);
                    }
                    return $s;
                })($amount)
                : number_format((float) $amount, 2, '.', '');
        };

        // Normalize Money inputs for totals from various shapes
        $makeMoney = function ($value, ?string $fallbackCurrency) use ($normalizeAmount): ?Money {
            if ($value instanceof Money) {
                return $value;
            }
            if (is_array($value)) {
                $amount = $value['amount'] ?? null;
                $curr = $value['currency'] ?? $fallbackCurrency ?? null;
                if ($amount !== null && is_string($curr)) {
                    return Money::fromString($normalizeAmount($amount), strtoupper($curr));
                }
                return null;
            }
            if (is_string($value) || is_numeric($value)) {
                $curr = $fallbackCurrency ?? 'CZK';
                return Money::fromString($normalizeAmount($value), strtoupper($curr));
            }
            return null;
        };

        // Accept multiple input keys for totals
        $subtotal = null;
        if (array_key_exists('subtotal', $data)) {
            $subtotal = $makeMoney($data['subtotal'], $currency);
        } elseif (array_key_exists('subtotal_money', $data)) {
            $subtotal = $makeMoney($data['subtotal_money'], $currency);
        }

        $totalTax = null;
        if (array_key_exists('total_tax', $data)) {
            $totalTax = $makeMoney($data['total_tax'], $currency);
        } elseif (array_key_exists('total_tax_money', $data)) {
            $totalTax = $makeMoney($data['total_tax_money'], $currency);
        }

        $totalAmount = null;
        if (array_key_exists('total_amount', $data)) {
            $totalAmount = $makeMoney($data['total_amount'], $currency);
        } elseif (array_key_exists('payment_amount_money', $data)) {
            $totalAmount = $makeMoney($data['payment_amount_money'], $currency);
        } elseif (array_key_exists('payment_amount', $data)) {
            $totalAmount = $makeMoney($data['payment_amount'], $currency);
        }

        return new self(
            client_id: isset($data['client_id']) ? (int)$data['client_id'] : null,
            supplier_id: isset($data['supplier_id']) ? (int)$data['supplier_id'] : null,
            number: $data['number'] ?? ($data['invoice_vs'] ?? null),
            issue_date: $data['issue_date'] ?? null,
            due_in: isset($data['due_in']) ? (int)$data['due_in'] : null,
            currency: $currency,
            template: $data['template'] ?? null,
            note: $data['note'] ?? ($data['invoice_text'] ?? null),
            //meta: $data['meta'] ?? null,
            user_id: isset($data['user_id']) ? (int)$data['user_id'] : null,

            payment_reference: $data['payment_reference'] ?? ($data['invoice_ss'] ?? null),
            constant_code: $data['constant_code'] ?? ($data['invoice_ks'] ?? null),
            tax_point_date: $data['tax_point_date'] ?? null,
            subtotal: $subtotal,
            total_tax: $totalTax,
            total_amount: $totalAmount,
            payment_method_id: isset($data['payment_method_id']) ? (int)$data['payment_method_id'] : null,
            invoice_text: $data['invoice_text'] ?? null,
            items: $data['items'] ?? [],
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
            logo: $data['logo'] ?? $data['invoice_logo'] ?? null,
            status: $data['status'] ?? (int)$data['payment_status_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        $properties = get_object_vars($this);
        return array_filter($properties, fn($v) => $v !== null);
    }
}
