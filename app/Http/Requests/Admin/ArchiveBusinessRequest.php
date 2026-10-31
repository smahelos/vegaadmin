<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Party\ValueObjects\BusinessId;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ArchiveBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_user()?->can('can_archive_business') === true;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        return [
            'replacement_business_id' => [
                'nullable',
                'integer',
                Rule::exists('businesses', 'id')
                    ->whereNull('archived_at')
                    ->whereNot('id', (int) $this->route('id'))
                    ->where(fn (Builder $query): Builder => $query->where('user_id',
                        fn (Builder $owner): Builder => $owner->select('user_id')
                            ->from('businesses')->where('id', (int) $this->route('id')))),
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
