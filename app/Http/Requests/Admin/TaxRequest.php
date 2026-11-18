<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class TaxRequest extends FormRequest
{
    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ];
    }

    /**
     * Attribute translations
     */
    public function attributes(): array
    {
        return [
            'name' => __('taxes.fields.name'),
            'rate' => __('taxes.fields.rate'),
        ];
    }
}
