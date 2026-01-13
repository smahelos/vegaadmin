<?php

namespace App\Domain\Payment\ValueObjects;

use InvalidArgumentException;

/**
 * Helper exposing supported currencies and min/max limits for PaymentAmount.
 * Single source of truth for limits so other classes (rules, UI) can query
 * without depending on internal constants of PaymentAmount.
 */
class PaymentAmountLimits
{
	/**
	 * @var array<string,float>
	 */
	private const MAX_LIMITS = [
		'CZK' => 10000000.0,
		'EUR' => 500000.0,
		'USD' => 500000.0,
	];

	private const MIN = 0.01;

	/**
	 * Return all supported ISO currency codes.
	 *
	 * @return string[]
	 */
	public static function supportedCurrencies(): array
	{
		return array_keys(self::MAX_LIMITS);
	}

	/**
	 * Minimum allowed amount for any currency.
	 */
	public static function minAmount(): float
	{
		return self::MIN;
	}

	/**
	 * Maximum allowed amount for given currency code (case-insensitive).
	 */
	public static function maxFor(string $currency): float
	{
		$currency = strtoupper($currency);
		if (!isset(self::MAX_LIMITS[$currency])) {
			throw new InvalidArgumentException("Unsupported currency: {$currency}");
		}
		return self::MAX_LIMITS[$currency];
	}

	/**
	 * Whether currency code is supported.
	 */
	public static function isSupported(string $currency): bool
	{
		return in_array(strtoupper($currency), self::supportedCurrencies(), true);
	}
}

