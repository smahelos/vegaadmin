<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Product\DTO\ProductWriteData;
use App\Http\Requests\Product\ProductInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Generate slug from name if not provided
        if (!$this->filled('slug') && $this->filled('name')) {
            $this->merge(
                [
                    'slug' => Str::slug($this->name),
                ],
            );
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $updating = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return ProductInput::rules(
            $this->user()?->id,
            $this->route('id') ?: $this->id,
            $updating,
        );
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
        return ProductInput::attributes();
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): void
    {
        $redirector = redirect();
        $redirect = $redirector->back();
        $redirect->withErrors($validator->errors());
        $redirect->withInput();
        $redirect->with(
            'error',
            __('products.messages.validation_failed'),
        );

        throw new ValidationException($validator, $redirect);
    }
}
