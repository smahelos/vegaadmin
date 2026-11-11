<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Product\DTO\ProductWriteData;
use App\Http\Requests\Product\ProductInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $updating = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            ...ProductInput::rules(
                $this->input('user_id'),
                $this->route('id') ?: $this->id,
                $updating,
            ),
            'user_id' => 'required|exists:users,id',
        ];
    }

    public function toWriteData(): ProductWriteData
    {
        return ProductWriteData::fromArray($this->validated());
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            ...ProductInput::attributes(),
            'user_id' => __('products.fields.user_id'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    public function prepareForValidation(): void
    {
        // Generate slug if not provided
        if (empty($this->slug)) {
            $this->merge(
                [
                    'slug' => Str::slug(
                        (string) $this->input('name'),
                    ),
                ],
            );
        }
    }
}
