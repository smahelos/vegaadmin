<?php

declare(strict_types=1);

namespace App\Http\Requests\Rules;

use App\Domain\Party\Exceptions\InvalidRegistrationNumberException;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Shared\Geography\Enums\Country;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A company number valid for its registration country, checked by RegistrationNumber.
 */
final class RegistrationNumberRule implements ValidationRule
{
    public function __construct(
        private readonly ?Country $country,
    ) {}

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        // A missing country is reported on the country field
        if ($this->country === null || ! is_string($value)) {
            return;
        }

        try {
            RegistrationNumber::fromParts($this->country, $value);
        } catch (InvalidRegistrationNumberException $e) {
            $fail('parties.validation.' . $e->problem->value)->translate();
        }
    }
}
