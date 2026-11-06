<?php

namespace Database\Factories;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    use DocumentParties;

    protected $model = Invoice::class;

    public function definition(): array
    {
        // Custom simple generators (vyhneme se numerify kvůli chybějícímu Base provideru ve Faker default konfiguraci)
        $rand4 = str_pad(
            (string) random_int(0, 9999),
            4,
            '0',
            STR_PAD_LEFT,
        );
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
            'invoice_vs' => 'INV-' . $rand4,
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
}
