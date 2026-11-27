<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The base and VAT of one rate of an issued document (D-050, point 7).
 */
class VatLine extends Model
{
    protected $fillable = [
        'invoice_id',
        'rate',
        'base',
        'vat',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'base' => 'decimal:2',
            'vat' => 'decimal:2',
        ];
    }
}
