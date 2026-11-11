<?php

namespace Database\Factories;

use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
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
            'business_id' => $this->businessOfUser(),
            'payment_status_id' => Status::factory(),
            'payment_method_id' => function (): int {
                $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value);

                return $paymentMethodQuery->value('id');
            },
            'payment_amount' => random_int(100, 10000),
            'payment_currency' => 'CZK',
            'invoice_text' => random_int(0, 1) ? 'Test invoice text' : null,
            ...$this->party('supplier'),
            ...$this->party('customer'),
        ];
    }

    /** A number in the InvoiceNumber format, so the invoice can be saved through the forms. */
    private function number(CarbonImmutable $now): string
    {
        $number = InvoiceNumber::fromYearAndSequence(
            $now->year,
            random_int(1, 999999),
        );

        return $number->getNumber();
    }
}
