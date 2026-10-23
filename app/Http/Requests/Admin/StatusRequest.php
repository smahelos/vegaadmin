<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        // Only allow updates if the user has permission to manage statuses
        return backpack_user()->can('can_create_edit_status');
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        $id = $this->route('id') ?: $this->id;
        $slugRule = Rule::unique('statuses', 'slug');
        if ($id) {
            $slugRule = $slugRule->ignore($id);
        }

        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                $slugRule,
            ],
            'category_id' => 'required|exists:status_categories,id',
            'color' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors
     */
    public function attributes(): array
    {
        return [
            'name' => __('statuses.fields.name'),
            'slug' => __('statuses.fields.slug'),
            'category_id' => __('statuses.fields.category_id'),
            'color' => __('statuses.fields.color'),
            'description' => __('statuses.fields.description'),
            'is_active' => __('statuses.fields.is_active'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Generate slug if not provided
        if (empty($this->slug)) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
