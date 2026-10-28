<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceCreateControllerFeatureTest extends TestCase
{
    #[Test]
    public function authenticated_user_can_access_invoice_create_page_and_view_has_expected_keys(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $response = $this->get('/cs/invoice/create');

        $response->assertStatus(200);
        $response->assertViewIs('frontend.invoices.create');
        $response->assertViewHasAll([
            'paymentMethods',
            'invoiceProducts',
            'statuses',
            'taxRates',
            'suggestedNumber',
            'limitsData',
            'fields',
            'contactId',
            'businessId',
        ]);
    }

    #[Test]
    public function create_form_preselects_the_bank_transfer(): void
    {
        Auth::login(User::factory()->create());
        $bankTransferId = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail()->id;

        $response = $this->get('/cs/invoice/create');

        $response->assertOk();
        $response->assertSee('<option value="' . $bankTransferId . '" selected>', false);
    }
}
