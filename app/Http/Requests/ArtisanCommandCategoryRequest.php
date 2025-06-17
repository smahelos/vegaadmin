<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArtisanCommandCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $id = $this->get('id') ?? 'NULL';
        
        return [
            'name' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255|unique:artisan_command_categories,slug,' . $id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('artisan_commands.name'),
            'slug' => trans('artisan_commands.slug'),
            'description' => trans('artisan_commands.description'),
            'is_active' => trans('artisan_commands.is_active'),
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('artisan_commands.validation.name_required'),
            'name.min' => trans('artisan_commands.validation.name_min'),
            'slug.required' => trans('artisan_commands.validation.slug_required'),
            'slug.unique' => trans('artisan_commands.validation.slug_unique'),
        ];
    }
}
