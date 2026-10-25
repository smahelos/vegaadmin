<?php

declare(strict_types=1);

namespace App\Http\Requests\Rules;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Uniqueness among one user's records, matching the (user_id, column) unique indexes:
 * two users may each have a client with the same IČO or a product with the same slug.
 */
final class UniquePerUser
{
    public static function rule(
        string $table,
        string $column,
        int|string|null $userId,
        int|string|null $ignoreId = null,
    ): Unique {
        return Rule::unique($table, $column)
            ->where('user_id', $userId)
            ->ignore($ignoreId);
    }
}
