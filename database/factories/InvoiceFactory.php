<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\User;
use App\Models\Supplier;
use App\Models\PaymentMethod;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        // Custom simple generators (vyhneme se numerify kvůli chybějícímu Base provideru ve Faker default konfiguraci)
        $rand4 = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $randKS = random_int(1000, 9999);
        $randSS = random_int(1000, 9999);

        return [
            'invoice_vs' => 'INV-' . $rand4,
            'invoice_ks' => (random_int(0,1) ? (string)$randKS : null),
            'invoice_ss' => (random_int(0,1) ? (string)$randSS : null),
            'issue_date' => now()->subDays(random_int(0,30))->toDateString(),
            'tax_point_date' => now()->subDays(random_int(0,30))->toDateString(),
            'due_in' => random_int(14, 30),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'supplier_id' => Supplier::factory(),
            'payment_status_id' => Status::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'payment_amount' => random_int(100, 10000),
            'payment_currency' => 'CZK',
            'invoice_text' => random_int(0,1) ? 'Test invoice text' : null,
        ];
    }
}
