<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtisanCommandCategory extends Model
{
    use CrudTrait, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Získá příkazy v této kategorii
     */
    public function commands(): HasMany
    {
        return $this->hasMany(ArtisanCommand::class, 'category_id');
    }
}
