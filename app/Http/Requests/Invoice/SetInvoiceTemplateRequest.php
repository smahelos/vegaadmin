<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use App\Domain\Invoice\Enums\InvoiceTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The template comes from the form or, without one, from the URL; either way it names the PDF view.
 */
final class SetInvoiceTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can('frontend.can_update_invoice') === true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'template' => [
                'required',
                Rule::enum(InvoiceTemplate::class),
            ],
        ];
    }

    public function template(): InvoiceTemplate
    {
        return InvoiceTemplate::from(
            $this->validated('template'),
        );
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('template')) {
            return;
        }

        $this->merge(
            [
                'template' => $this->route('template', InvoiceTemplate::DEFAULT->value),
            ],
        );
    }
}
