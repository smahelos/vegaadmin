<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\User\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InvoiceBatchActionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can(Permission::INVOICE_UPDATE) === true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'template' => [
                'sometimes',
                Rule::enum(InvoiceTemplate::class),
            ],
            'status' => [
                'sometimes',
                Rule::enum(InvoiceStatus::class),
            ],
            'mark_paid' => ['sometimes', 'boolean'],
        ];
    }
}
