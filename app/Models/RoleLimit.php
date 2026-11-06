<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleLimit extends Model
{
    protected $fillable = [
        'role_id',
        'invoices',
        'expenses',
        'businesses',
        'contacts',
        'products',
    ];

    protected function casts(): array
    {
        return [
            'invoices' => 'integer',
            'expenses' => 'integer',
            'businesses' => 'integer',
            'contacts' => 'integer',
            'products' => 'integer',
        ];
    }
}
