<?php

declare(strict_types=1);

namespace App\Livewire\Party\Forms;

use App\Domain\Party\DTO\InvoicingSettings;
use App\Http\Requests\Party\InvoicingSettingsInput;
use Illuminate\Support\Str;
use Livewire\Form;

/**
 * The invoicing section of the business form.
 */
final class InvoicingSettingsForm extends Form
{
    public bool $isVatPayer = false;

    public string $registerEntry = '';

    public string $documentLanguage = '';

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return InvoicingSettingsInput::rules(
            Str::camel(...),
        );
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return InvoicingSettingsInput::attributes(
            Str::camel(...),
        );
    }

    /**
     * Call after validate().
     */
    public function settings(): InvoicingSettings
    {
        return InvoicingSettingsInput::settings(
            [
                'is_vat_payer' => $this->isVatPayer,
                'register_entry' => $this->registerEntry,
                'document_language' => $this->documentLanguage,
            ],
        );
    }

    public function fillFromSettings(InvoicingSettings $settings): void
    {
        $this->isVatPayer = $settings->vatPayer;
        $this->registerEntry = $settings->registerEntry ?? '';
        $this->documentLanguage = $settings->documentLanguage->value;
    }
}
