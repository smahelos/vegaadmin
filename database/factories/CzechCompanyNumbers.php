<?php

declare(strict_types=1);

namespace Database\Factories;

trait CzechCompanyNumbers
{
    /**
     * A valid IČO: the seven digits of the base and the mod-11 check digit.
     */
    private function czechCompanyNumber(int $base): string
    {
        $digits = str_pad(
            (string) $base,
            7,
            '0',
            STR_PAD_LEFT,
        );
        $sum = 0;
        foreach (str_split($digits) as $position => $digit) {
            $sum += (int) $digit * (8 - $position);
        }

        return $digits . (11 - $sum % 11) % 10;
    }
}
