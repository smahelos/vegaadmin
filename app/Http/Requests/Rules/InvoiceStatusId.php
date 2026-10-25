<?php

declare(strict_types=1);

namespace App\Http\Requests\Rules;

use App\Domain\Invoice\Enums\InvoiceStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Id of a status row that is an invoice status; expense statuses share the table.
 */
final class InvoiceStatusId
{
    public static function rule(): Exists
    {
        return Rule::exists('statuses', 'id')->whereIn('slug', array_column(InvoiceStatus::cases(), 'value'));
    }
}
