<?php

namespace App\Models;

use App\Infrastructure\Shared\Locale\Traits\HasPreferredLocale;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Client extends Model
{
    use CrudTrait, HasFactory, HasPreferredLocale, HasRoles, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    protected $table = 'clients';

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Boot method for the model
     * Sets all other clients of the user to is_default = false
     * if this client is set as default
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $model): void {
            // If this client is set as default, unset default status for other clients
            if ($model->is_default) {
                self::where('user_id', $model->user_id)
                    ->where(
                        'id',
                        '!=',
                        $model->id,
                    )
                    ->update(['is_default' => false]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get invoices associated with this client
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the user that owns the client
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Get preferred locale of the client
     */
    public function preferredLocale()
    {
        return $this->getPreferredLocale();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
    /**
     * Mutator for is_default attribute to ensure boolean value
     */
    public function setIsDefaultAttribute($value): void
    {
        $this->attributes['is_default'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
