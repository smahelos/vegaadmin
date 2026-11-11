<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Enums\Permission;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ArchiveBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = backpack_user();

        return $user?->can(Permission::BUSINESS_ARCHIVE) === true;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        $replacementExists = Rule::exists('businesses', 'id');
        $replacementExists->whereNull('archived_at');
        $replacementExists->whereNot(
            'id',
            (int) $this->route('id'),
        );
        $replacementExists->where(
            fn (Builder $query): Builder => $query->where(
                'user_id',
                function (Builder $owner): void {
                    $owner->select('user_id');
                    $owner->from('businesses');
                    $owner->where(
                        'id',
                        (int) $this->route('id'),
                    );
                },
            ),
        );

        return [
            'replacement_business_id' => [
                'nullable',
                'integer',
                $replacementExists,
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['replacement_business_id' => __('businesses.fields.new_default')];
    }

    public function replacement(): ?BusinessId
    {
        $id = $this->validated('replacement_business_id');

        return $id === null ? null : BusinessId::fromInt((int) $id);
    }
}
