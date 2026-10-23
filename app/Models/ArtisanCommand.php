<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtisanCommand extends Model
{
    use CrudTrait, HasFactory;

    protected $fillable = [
        'name',
        'signature',
        'description',
        'category_id',
        'is_active',
        'command',
        'parameters_description',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Získá kategorii, do které příkaz patří
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ArtisanCommandCategory::class, 'category_id');
    }
}
