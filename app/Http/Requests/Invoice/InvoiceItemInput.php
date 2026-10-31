<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Shared\Unit\Enums\Unit;
use Closure;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

final class InvoiceItemInput
{
    /** @return array<string, mixed> */
    public static function rules(
        ?int $userId,
        bool $guest,
        bool $required,
    ): array {
        return [
            'invoice-products' => [
                $required ? 'required' : 'sometimes',
                'array',
                'list',
                'min:' . ($required ? '1' : '0'),
                'max:500',
            ],
            'invoice-products.*' => ['array'],
            'invoice-products.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'invoice-products.*.quantity' => [
                'required',
                'numeric',
                'decimal:0,3',
                'gt:0',
                'max:99999.999',
            ],
            'invoice-products.*.unit' => ['nullable', Rule::enum(Unit::class)],
            'invoice-products.*.price' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:99999999.99',
            ],
            'invoice-products.*.currency' => [
                'required',
                Rule::enum(Currency::class),
                'same:payment_currency',
            ],
            'invoice-products.*.tax_rate' => [
                'required',
                'numeric',
                self::taxRateRule(),
            ],
            'invoice-products.*.product_id' => $guest
                ? ['prohibited']
                : [
                    'nullable',
                    'integer',
                    Rule::exists('products', 'id')->where('user_id', $userId),
                ],
            'invoice-products.*.category' => [
                'nullable',
                'string',
                'max:255',
            ],
            'invoice-products.*.description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'invoice-products' => __('invoices.fields.invoice_items'),
            'invoice-products.*.name' => __('invoices.fields.item_name'),
            'invoice-products.*.quantity' => __('invoices.fields.item_quantity'),
            'invoice-products.*.unit' => __('invoices.fields.item_unit'),
            'invoice-products.*.price' => __('invoices.fields.item_price'),
            'invoice-products.*.currency' => __('invoices.fields.item_currency'),
            'invoice-products.*.tax_rate' => __('invoices.fields.item_tax_rate'),
            'invoice-products.*.product_id' => __('invoices.fields.item_product'),
            'invoice-products.*.category' => __('invoices.fields.item_category'),
            'invoice-products.*.description' => __('invoices.fields.item_description'),
        ];
    }

    /**
     * A line may carry only a rate the invoice form offers: 0 % or one of the VAT rates in the taxes table.
     * The rates are loaded once, when the first numeric rate reaches the rule.
     */
    private static function taxRateRule(): Closure
    {
        $allowed = null;

        return function (
            string $attribute,
            mixed $value,
            Closure $fail,
        ) use (&$allowed): void {
            if (! is_numeric($value)) {
                return;
            }
            $allowed ??= self::allowedTaxRates();
            if (! in_array(
                self::normalizedRate((string) $value),
                $allowed,
                true,
            )) {
                $fail(__('invoices.validation.tax_rate', ['rates' => self::formattedRates($allowed)]));
            }
        };
    }

    /**
     * @return list<string>
     */
    private static function allowedTaxRates(): array
    {
        $rates = array_map(
            fn (int|float|string $rate): string => self::normalizedRate((string) $rate),
            ['0', ...array_values(app(TaxDtoReadRepositoryInterface::class)->getDphRatesForDropdown())],
        );
        $rates = array_values(array_unique($rates));
        sort($rates, SORT_NUMERIC);

        return $rates;
    }

    /**
     * @param  list<string>  $rates
     */
    private static function formattedRates(array $rates): string
    {
        return implode(', ', array_map(
            fn (string $rate): string => (string) Number::percentage(
                (float) $rate,
                maxPrecision: 2,
                locale: app()->getLocale(),
            ),
            $rates,
        ));
    }

    /** "21", "21.0" and "21.00" are the same rate. */
    private static function normalizedRate(string $rate): string
    {
        $rate = ltrim(trim($rate), '+');
        if (str_contains($rate, '.')) {
            $rate = rtrim(rtrim($rate, '0'), '.');
        }

        return $rate === '' ? '0' : $rate;
    }
}
