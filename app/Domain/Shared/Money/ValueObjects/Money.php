<?php

namespace App\Domain\Shared\Money\ValueObjects;

use InvalidArgumentException;
use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;

/**
 * Money value object.
 * Immutable representation of a non-negative monetary amount with currency code.
 */
class Money
{
    /** @var string ISO 4217 uppercase currency code */
    private string $currency;

    /** @var string Canonical string decimal representation (no scientific, trimmed zeros) */
    private string $amount;

    /** Rounding strategy: standard half up (5 rounds up). */
    public const ROUND_HALF_UP = 'HALF_UP';
    
    /** Rounding strategy: bankers (half to even). */
    public const ROUND_BANKERS = 'BANKERS';

    /**
     * Private constructor to enforce factory usage.
     */
    private function __construct(string $amount, string $currency)
    {
        $this->currency = strtoupper($currency);
        $this->amount = $amount; // normalized
    }

    /**
     * Create Money from float (temporary helper – lossy, will be removed later).
     * 
     * @param float $amount Non-negative amount
     * @param string $currency ISO 4217 currency code
     * @throws InvalidArgumentException if currency is invalid or amount is negative
     * @return self
     */
    public static function fromFloat(float $amount, string $currency): self
    {
        self::assertCurrency($currency);
        self::assertAmount($amount);
        $normalized = number_format($amount, 2, '.', '');
        return new self($normalized, $currency);
    }

    /**
     * Create Money from string decimal amount (up to 8 fractional digits).
     * @param string $amount Non-negative amount
     * @param string $currency ISO 4217 currency code
     * @throws InvalidArgumentException if currency is invalid or amount is negative
     * @return self
     */
    public static function fromString(string $amount, string $currency): self
    {
        self::assertCurrency($currency);
        if (!preg_match('/^\d+\.?\d{0,8}$/', $amount)) {
            throw new InvalidArgumentException('Invalid money amount format: ' . $amount);
        }
        return new self(self::normalizeStringAmount($amount), $currency);
    }

    /**
     * Summary of getAmount
     * @return string
     */
    public function getAmount(): string { return $this->amount; }

    /**
     * Summary of getCurrency
     * @return string
     */
    public function getCurrency(): string { return $this->currency; }
    
    /**
     * Summary of toFloat
     * @return float
     */
    public function toFloat(): float { return (float)$this->amount; }
    
    /**
     * Summary of __toString
     * @return string
     */
    public function __toString(): string { return $this->amount . ' ' . $this->currency; }

    /** Add another Money of the same currency.
     * @param Money $other
     * @throws InvalidArgumentException if currencies do not match
     * @return self
     */
    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        $result = $this->addRaw($this->amount, $other->amount);
        return new self($result, $this->currency);
    }

    /** Subtract another Money (result must stay non-negative).
     * @param Money $other
     * @throws InvalidArgumentException if currencies do not match
     * @return self
     */
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        if ($this->isLessThan($other)) {
            throw new InvalidArgumentException('Resulting money would be negative');
        }
        $result = $this->subtractRaw($this->amount, $other->amount);
        return new self($result, $this->currency);
    }

    /** Multiply by a non-negative integer factor.
     * @param int $factor Non-negative integer factor
     * @throws InvalidArgumentException if factor is negative
     * @return self
     */
    public function multiply(int $factor): self
    {
        if ($factor < 0) { throw new InvalidArgumentException('Factor must be >= 0'); }
        if ($factor === 0) { return new self('0', $this->currency); }
        [$int, $frac] = $this->splitDecimal($this->amount);
        $digits = $int . $frac; // raw digits without decimal point
        $product = $this->multiplyIntegerStringByInt($digits, $factor);
        $scale = strlen($frac);
        if ($scale > 0) {
            $product = str_pad($product, $scale + 1, '0', STR_PAD_LEFT);
            $integer = substr($product, 0, -$scale);
            $fraction = substr($product, -$scale);
            $result = $integer . '.' . $fraction;
        } else {
            $result = $product;
        }
        return new self(self::normalizeStringAmount($result), $this->currency);
    }

    /**
     * Divide by positive integer divisor using truncation (no rounding) up to scale digits.
     * @param int $divisor Positive integer divisor
     * @param int $scale Number of fractional digits (0-8)
     * @throws InvalidArgumentException if divisor is not positive
     * @throws InvalidArgumentException if scale is not between 0 and 8
     * @return self
     */
    public function divide(int $divisor, int $scale = 8): self
    {
        if ($divisor <= 0) { throw new InvalidArgumentException('Divisor must be > 0'); }
        if ($scale < 0 || $scale > 8) { throw new InvalidArgumentException('Scale must be between 0 and 8'); }
        [$int, $frac] = $this->splitDecimal($this->amount);
        $digits = ltrim($int . $frac, '0');
        $originalScale = strlen($frac);
        if ($digits === '') { return new self('0', $this->currency); }

        // Long division over integer representation
        $quotient = '';
        $remainder = 0;
        $len = strlen($digits);
        for ($i = 0; $i < $len; $i++) {
            $remainder = $remainder * 10 + (int)$digits[$i];
            $q = intdiv($remainder, $divisor);
            $quotient .= (string)$q;
            $remainder -= $q * $divisor;
        }
        $fraction = '';
        if ($scale > 0) {
            for ($d = 0; $d < $scale && $remainder > 0; $d++) {
                $remainder *= 10;
                $q = intdiv($remainder, $divisor);
                $fraction .= (string)$q;
                $remainder -= $q * $divisor;
            }
        }

        // Insert decimal according to original scale
        if ($originalScale > 0) {
            $totalLen = strlen($quotient);
            if ($totalLen <= $originalScale) {
                $quotient = str_pad($quotient, $originalScale + 1, '0', STR_PAD_LEFT);
            }
            $integer = substr($quotient, 0, -$originalScale);
            $baseFraction = substr($quotient, -$originalScale);
            $combinedFraction = $baseFraction . $fraction;
            $result = $integer . (strlen($combinedFraction) ? '.' . $combinedFraction : '');
        } elseif ($fraction !== '') {
            $result = $quotient . '.' . $fraction;
        } else {
            $result = $quotient;
        }
        return new self(self::normalizeStringAmount($result), $this->currency);
    }

    /**
     * Divide with rounding (HALF_UP or BANKERS) up to scale digits.
     * Algorithm: produce (scale + 1) fractional digits then apply rounding strategy.
     * @param int $divisor Positive integer divisor
     * @param int $scale Number of fractional digits (0-8)
     * @param string $strategy Rounding strategy (HALF_UP or BANKERS)
     * @return self
     */
    public function divideRounding(int $divisor, int $scale = 2, string $strategy = self::ROUND_HALF_UP): self
    {
        if ($divisor <= 0) { throw new InvalidArgumentException('Divisor must be > 0'); }
        if ($scale < 0 || $scale > 8) { throw new InvalidArgumentException('Scale must be between 0 and 8'); }
        if (!in_array($strategy, [self::ROUND_HALF_UP, self::ROUND_BANKERS], true)) {
            throw new InvalidArgumentException('Invalid rounding strategy: ' . $strategy);
        }
        [$int, $frac] = $this->splitDecimal($this->amount);
        $digits = ltrim($int . $frac, '0');
        $origScale = strlen($frac);
        if ($digits === '') { return new self('0', $this->currency); }

        // Perform long division for existing digits
        $quotient = '';
        $remainder = 0;
        $digitsLen = strlen($digits);
        for ($i = 0; $i < $digitsLen; $i++) {
            $remainder = $remainder * 10 + (int)$digits[$i];
            $q = intdiv($remainder, $divisor);
            $quotient .= (string)$q;
            $remainder -= $q * $divisor;
        }

        // Split integer/base fraction relative to original scale
        if ($origScale > 0) {
            $totalLen = strlen($quotient);
            if ($totalLen <= $origScale) {
                $quotient = str_pad($quotient, $origScale + 1, '0', STR_PAD_LEFT);
            }
            $integer = substr($quotient, 0, -$origScale);
            $baseFraction = substr($quotient, -$origScale);
        } else {
            $integer = $quotient;
            $baseFraction = '';
        }

        // Need scale + 1 digits for rounding decision
        $targetDigits = $scale + 1;
        $extraFraction = '';
        while (strlen($baseFraction . $extraFraction) < $targetDigits) {
            $remainder *= 10;
            $q = intdiv($remainder, $divisor);
            $extraFraction .= (string)$q;
            $remainder -= $q * $divisor;
            if ($remainder === 0 && strlen($baseFraction . $extraFraction) >= $targetDigits) { break; }
            if ($remainder === 0 && strlen($baseFraction . $extraFraction) < $targetDigits) {
                // pad zeros if remainder finished early
                $extraFraction = str_pad($extraFraction, $targetDigits - strlen($baseFraction), '0', STR_PAD_RIGHT);
                break;
            }
        }

        $fullFraction = $baseFraction . $extraFraction;
        if (strlen($fullFraction) < $targetDigits) {
            $fullFraction = str_pad($fullFraction, $targetDigits, '0', STR_PAD_RIGHT);
        }

        $keptFraction = $scale > 0 ? substr($fullFraction, 0, $scale) : '';
        $roundingDigit = $scale >= 0 ? (int)substr($fullFraction, $scale, 1) : 0; // digit after kept part
        $hasFurther = $remainder > 0 || strlen($fullFraction) > $scale + 1; // indicates more non-zero info beyond rounding digit

        if ($this->shouldRoundUp($strategy, $roundingDigit, $keptFraction, $hasFurther, $integer, $scale)) {
            [$integer, $keptFraction] = $this->incrementDecimal($integer, $keptFraction);
        }
        $result = $integer . ($keptFraction !== '' ? '.' . $keptFraction : '');
        return new self(self::normalizeStringAmount($result), $this->currency);
    }

    /** Decide if rounding up is needed based on strategy. */
    private function shouldRoundUp(string $strategy, int $digit, string $keptFraction, bool $hasFurther, string $integerPart, int $scale): bool
    {
        if ($digit < 5) { return false; }
        if ($digit > 5) { return true; }
        if ($hasFurther) { return true; }
        if ($strategy === self::ROUND_HALF_UP) { return true; }
        $lastDigit = $scale === 0 ? (int)substr($integerPart, -1) : ($keptFraction === '' ? 0 : (int)substr($keptFraction, -1));
        return $lastDigit % 2 === 1;
    }

    /** Increment decimal number represented by integer + fraction strings (fraction length preserved). */
    private function incrementDecimal(string $integer, string $fraction): array
    {
        if ($fraction === '') {
            $integer = $this->addIntegerStrings($integer, '1');
            return [$integer, $fraction];
        }
        $rev = strrev($fraction); $carry = 1; $out = '';
        for ($i = 0; $i < strlen($rev); $i++) {
            $d = (int)$rev[$i] + $carry;
            if ($d === 10) { $out .= '0'; $carry = 1; } else { $out .= (string)$d; $carry = 0; }
        }
        if ($carry === 1) { $integer = $this->addIntegerStrings($integer, '1'); }
        $fraction = strrev($out);
        return [$integer, $fraction];
    }

    /** Equality (same currency & numeric amount). */
    public function equals(Money $other): bool
    {
        if ($this->currency !== $other->currency) { return false; }
        return $this->compareRaw($this->amount, $other->amount) === 0;
    }

    /** Determine if this amount is less than other (same currency). */
    private function isLessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->compareRaw($this->amount, $other->amount) < 0;
    }

    /** Assert same currency. */
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currency mismatch: ' . $this->currency . ' vs ' . $other->currency);
        }
    }

    /** Add two normalized decimals. */
    private function addRaw(string $a, string $b): string
    {
        [$ai, $af] = $this->splitDecimal($a);
        [$bi, $bf] = $this->splitDecimal($b);
        $scale = max(strlen($af), strlen($bf));
        $af = str_pad($af, $scale, '0', STR_PAD_RIGHT);
        $bf = str_pad($bf, $scale, '0', STR_PAD_RIGHT);
        $sum = $this->addIntegerStrings($ai . $af, $bi . $bf);
        if ($scale > 0) {
            $sum = str_pad($sum, $scale + 1, '0', STR_PAD_LEFT);
            $integer = substr($sum, 0, -$scale);
            $fraction = substr($sum, -$scale);
            $result = $integer . '.' . $fraction;
        } else { $result = $sum; }
        return self::normalizeStringAmount($result);
    }

    /** Subtract normalized decimal b from a (assumes a >= b). */
    private function subtractRaw(string $a, string $b): string
    {
        [$ai, $af] = $this->splitDecimal($a); [$bi, $bf] = $this->splitDecimal($b);
        $scale = max(strlen($af), strlen($bf));
        $af = str_pad($af, $scale, '0', STR_PAD_RIGHT);
        $bf = str_pad($bf, $scale, '0', STR_PAD_RIGHT);
        $diff = $this->subtractIntegerStrings($ai . $af, $bi . $bf);
        if ($scale > 0) {
            $diff = str_pad($diff, $scale + 1, '0', STR_PAD_LEFT);
            $integer = substr($diff, 0, -$scale);
            $fraction = substr($diff, -$scale);
            $result = $integer . '.' . $fraction;
        } else { $result = $diff; }
        return self::normalizeStringAmount($result);
    }

    /** Compare two normalized decimals. */
    private function compareRaw(string $a, string $b): int
    {
        [$ai, $af] = $this->splitDecimal($a); [$bi, $bf] = $this->splitDecimal($b);
        if (strlen($ai) !== strlen($bi)) { return strlen($ai) < strlen($bi) ? -1 : 1; }
        if ($ai !== $bi) { return $ai < $bi ? -1 : 1; }
        $scale = max(strlen($af), strlen($bf));
        $afP = str_pad($af, $scale, '0', STR_PAD_RIGHT);
        $bfP = str_pad($bf, $scale, '0', STR_PAD_RIGHT);
        if ($afP === $bfP) { return 0; }
        return $afP < $bfP ? -1 : 1;
    }

    /** Split decimal into integer & fractional parts. */
    private function splitDecimal(string $value): array
    {
        if (!str_contains($value, '.')) { return [$value, '']; }
        [$i, $f] = explode('.', $value, 2);
        return [$i === '' ? '0' : $i, $f];
    }

    /** Add two positive integer strings. */
    private function addIntegerStrings(string $a, string $b): string
    {
        $carry = 0; $out = '';
        $a = ltrim($a, '0'); $b = ltrim($b, '0');
        if ($a === '') { $a = '0'; } if ($b === '') { $b = '0'; }
        $i = strlen($a) - 1; $j = strlen($b) - 1;
        while ($i >= 0 || $j >= 0 || $carry) {
            $da = $i >= 0 ? (int)$a[$i--] : 0; $db = $j >= 0 ? (int)$b[$j--] : 0;
            $sum = $da + $db + $carry; $out = ($sum % 10) . $out; $carry = intdiv($sum, 10);
        }
        return ltrim($out, '0') === '' ? '0' : ltrim($out, '0');
    }

    /** Subtract integer string b from a (a >= b). */
    private function subtractIntegerStrings(string $a, string $b): string
    {
        $a = ltrim($a, '0'); $b = ltrim($b, '0'); if ($b === '') { $b = '0'; }
        $borrow = 0; $out = ''; $i = strlen($a) - 1; $j = strlen($b) - 1;
        while ($i >= 0) {
            $da = (int)$a[$i] - $borrow; $i--; $db = $j >= 0 ? (int)$b[$j] : 0; $j--;
            if ($da < $db) { $da += 10; $borrow = 1; } else { $borrow = 0; }
            $out = ($da - $db) . $out;
        }
        $out = ltrim($out, '0');
        return $out === '' ? '0' : $out;
    }

    /** Multiply integer numeric string by non-negative integer factor. */
    private function multiplyIntegerStringByInt(string $digits, int $factor): string
    {
        $digits = ltrim($digits, '0');
        if ($digits === '' || $factor === 0) { return '0'; }
        $carry = 0; $out = '';
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $prod = ((int)$digits[$i]) * $factor + $carry;
            $out = ($prod % 10) . $out;
            $carry = intdiv($prod, 10);
        }
        if ($carry > 0) { $out = $carry . $out; }
        return ltrim($out, '0') === '' ? '0' : ltrim($out, '0');
    }

    /** Validate ISO currency code (A-Z, length 3). */
    private static function assertCurrency(string $currency): void
    {
        if (!preg_match('/^[A-Z]{3}$/', strtoupper($currency))) {
            throw new InvalidArgumentException('Invalid ISO currency code: ' . $currency);
        }
    }

    /** Validate raw float amount (must be >= 0). */
    private static function assertAmount(float $amount): void
    {
        if ($amount < 0) { throw new InvalidArgumentException('Money amount must be >= 0'); }
    }

    /** Normalize decimal string (strip leading/trailing zeros as appropriate). */
    private static function normalizeStringAmount(string $amount): string
    {
        $amount = ltrim($amount, '0');
        if ($amount === '' || $amount[0] === '.') { $amount = '0' . $amount; }
        if (str_contains($amount, '.')) {
            $amount = rtrim(rtrim($amount, '0'), '.');
            if ($amount === '') { $amount = '0'; }
        }
        return $amount;
    }

    /** Convert to another currency using exchange service. */
    public function convertTo(string $targetCurrency, CurrencyExchangeServiceInterface $exchange, int $scale = 2, string $strategy = self::ROUND_HALF_UP): self
    {
        $targetCurrency = strtoupper($targetCurrency);
        if ($targetCurrency === $this->currency) {
            return new self($this->amount, $this->currency);
        }
        self::assertCurrency($targetCurrency);
        if ($scale < 0 || $scale > 8) { throw new InvalidArgumentException('Scale must be between 0 and 8'); }
        if (!in_array($strategy, [self::ROUND_HALF_UP, self::ROUND_BANKERS], true)) {
            throw new InvalidArgumentException('Invalid rounding strategy: ' . $strategy);
        }
        $rate = $exchange->getExchangeRate($this->currency, $targetCurrency);
        if ($rate === null || $rate <= 0) {
            throw new InvalidArgumentException('Unable to obtain valid exchange rate for conversion');
        }
        $rateStr = rtrim(rtrim(number_format($rate, 8, '.', ''), '0'), '.');
        if ($rateStr === '') { $rateStr = '0'; }
        [$aInt, $aFrac] = $this->splitDecimal($this->amount);
        [$rInt, $rFrac] = $this->splitDecimal($rateStr);
        $aDigits = ltrim($aInt . $aFrac, '0');
        // BUGFIX: původně použit + (aritmetika) místo . (konkatenace) => způsobovalo chybný výpočet kurzových násobků
        $rDigits = ltrim($rInt . $rFrac, '0');
        $aScale = strlen($aFrac);
        $rScale = strlen($rFrac);
        if ($aDigits === '') { return new self('0', $targetCurrency); }
        if ($rDigits === '') { throw new InvalidArgumentException('Invalid exchange rate format'); }
        $productDigits = $this->multiplyIntegerStringsGeneric($aDigits, $rDigits);
        $totalScale = $aScale + $rScale;
        if ($totalScale > 0) {
            if (strlen($productDigits) <= $totalScale) {
                $productDigits = str_pad($productDigits, $totalScale + 1, '0', STR_PAD_LEFT);
            }
            $integer = substr($productDigits, 0, -$totalScale);
            $fraction = substr($productDigits, -$totalScale);
            $raw = $integer . ($fraction !== '' ? '.' . $fraction : '');
        } else { $raw = $productDigits; }
        $normalizedRaw = self::normalizeStringAmount($raw);
        if ($scale === 0) {
            if (str_contains($normalizedRaw, '.')) {
                [$intPart, $fracPart] = explode('.', $normalizedRaw, 2);
            } else { $intPart = $normalizedRaw; $fracPart = ''; }
            $roundDigit = $fracPart !== '' ? (int)substr($fracPart, 0, 1) : 0;
            $hasFurther = strlen($fracPart) > 1;
            $keptFraction = '';
            if ($this->shouldRoundUp($strategy, $roundDigit, $keptFraction, $hasFurther, $intPart, 0)) {
                [$intPart, $keptFraction] = $this->incrementDecimal($intPart, $keptFraction);
            }
            $result = $intPart;
        } else {
            if (!str_contains($normalizedRaw, '.')) {
                $intPart = $normalizedRaw; $fracFull = '';
            } else {
                [$intPart, $fracFull] = explode('.', $normalizedRaw, 2);
            }
            if ($fracFull === '') { $fracFull = str_repeat('0', $scale + 1); }
            if (strlen($fracFull) < $scale + 1) { $fracFull = str_pad($fracFull, $scale + 1, '0', STR_PAD_RIGHT); }
            $kept = substr($fracFull, 0, $scale);
            $roundDigit = (int)substr($fracFull, $scale, 1);
            $hasFurther = strlen($fracFull) > $scale + 1;
            if ($this->shouldRoundUp($strategy, $roundDigit, $kept, $hasFurther, $intPart, $scale)) {
                [$intPart, $kept] = $this->incrementDecimal($intPart, $kept);
            }
            $result = $intPart . ($kept !== '' ? '.' . $kept : '');
        }
        return new self(self::normalizeStringAmount($result), $targetCurrency);
    }

    /** Generic integer string multiplication. */
    private function multiplyIntegerStringsGeneric(string $a, string $b): string
    {
        if ($a === '' || $b === '') { return '0'; }
        if ($a === '0' || $b === '0') { return '0'; }
        $aLen = strlen($a); $bLen = strlen($b);
        $res = array_fill(0, $aLen + $bLen, 0);
        for ($i = $aLen - 1; $i >= 0; $i--) {
            $carry = 0; $ai = (int)$a[$i];
            for ($j = $bLen - 1; $j >= 0; $j--) {
                $idx = $i + $j + 1;
                $prod = $ai * (int)$b[$j] + $res[$idx] + $carry;
                $res[$idx] = $prod % 10;
                $carry = intdiv($prod, 10);
            }
            $res[$i] += $carry;
        }
        $out = ltrim(implode('', $res), '0');
        return $out === '' ? '0' : $out;
    }
}
