<?php

declare(strict_types=1);

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use CrudTrait;

    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'short_name',
        'registration_country',
        'company_number',
        'vat_number',
        'email',
        'phone',
        'contact_person',
        'logo',
        'note',
        'default_bank_account_id',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }
}
