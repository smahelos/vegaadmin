<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Enums\LimitedEntity;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class RoleLimit extends Model
{
    use CrudTrait;

    protected $fillable = [
        'role_id',
        'invoices',
        'expenses',
        'businesses',
        'contacts',
        'products',
    ];

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function limit(LimitedEntity $entity): ?int
    {
        return match ($entity) {
            LimitedEntity::INVOICE => $this->invoices,
            LimitedEntity::EXPENSE => $this->expenses,
            LimitedEntity::BUSINESS => $this->businesses,
            LimitedEntity::CONTACT => $this->contacts,
            LimitedEntity::PRODUCT => $this->products,
        };
    }

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
