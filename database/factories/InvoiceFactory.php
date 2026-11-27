<?php

namespace Database\Factories;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Translation\Enums\Language;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    use DocumentParties;

    protected $model = Invoice::class;

    public function definition(): array
    {
        $randKS = random_int(1000, 9999);
        $randSS = random_int(1000, 9999);
        $now = CarbonImmutable::now();
        $issueDate = $now->subDays(
            random_int(0, 30),
        );
        $taxPointDate = $now->subDays(
            random_int(0, 30),
        );

        return [
            'invoice_vs' => $this->number($now),
            'invoice_ks' => (random_int(0, 1) ? (string) $randKS : null),
            'invoice_ss' => (random_int(0, 1) ? (string) $randSS : null),
            'issue_date' => $issueDate->toDateString(),
            'tax_point_date' => $taxPointDate->toDateString(),
            'due_in' => random_int(14, 30),
            'user_id' => User::factory(),
            // After user_id, which the closure reads
            'issued_at' => $now,
            'issued_by' => fn (array $attributes): int => $attributes['user_id'],
            // Not a payer, so the totals come from the lines and no VAT lines are needed
            'is_vat_payer' => false,
            'business_id' => $this->businessOfUser(),
            'payment_status' => InvoiceStatus::PENDING->value,
            'payment_method_id' => function (): int {
                $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value);

                return $paymentMethodQuery->value('id');
            },
            'payment_amount' => random_int(100, 10000),
            'payment_currency' => 'CZK',
            'invoice_text' => random_int(0, 1) ? 'Test invoice text' : null,
            'template' => InvoiceTemplate::DEFAULT->value,
            'document_language' => Language::CS->value,
            ...$this->party('supplier'),
            ...$this->party('customer'),
        ];
    }

    /**
     * Not issued yet: no number, no dates, no copied invoicing settings (D-050).
     */
    public function draft(): static
    {
        return $this->state(
            [
                'invoice_vs' => null,
                'issue_date' => null,
                'tax_point_date' => null,
                'issued_at' => null,
                'issued_by' => null,
                'is_vat_payer' => null,
            ],
        );
    }

    /** A number in the InvoiceNumber format, so the invoice can be saved through the forms. */
    private function number(CarbonImmutable $now): string
    {
        $number = InvoiceNumber::fromYearAndSequence(
            $now->year,
            random_int(1, 999999),
        );

        return $number->value;
    }
}
