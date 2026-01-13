<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class InvoiceCreateControllerFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    #[Test]
    public function authenticated_user_can_access_invoice_create_page_and_view_has_expected_keys(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->get('/cs/invoice/create');

        $response->assertStatus(200);
        $response->assertViewIs('frontend.invoices.create');
        $response->assertViewHasAll([
            'clients', 'suppliers', 'paymentMethods', 'invoiceProducts', 'statuses',
            'taxRates', 'banks', 'banksData', 'suggestedNumber', 'userInfo', 'itemUnits',
            'limitsData', 'fields'
        ]);
    }
}
