<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoice;

use App\Domain\User\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

final class MarkInvoicePaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can(Permission::INVOICE_UPDATE) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
