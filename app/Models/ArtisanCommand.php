<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class ArtisanCommand extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'is_active',
        'command',
        'parameters_description',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Získá kategorii, do které příkaz patří
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ArtisanCommandCategory::class, 'category_id');
    }
}
