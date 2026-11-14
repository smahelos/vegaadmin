<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

final class SubscribeRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'gateway' => 'required|string|in:gopay',
        ];
    }

    public function gateway(): string
    {
        return (string) $this->validated('gateway');
    }
}
