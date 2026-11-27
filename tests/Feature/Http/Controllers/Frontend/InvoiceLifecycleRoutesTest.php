<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\Invoice;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

/**
 * Copying, deleting and the payment status of an invoice (D-050): who may call the routes. Issuing is a
 * Livewire dialog (`IssueInvoiceDialogTest`).
 */
final class InvoiceLifecycleRoutesTest extends TestCase
{
    use BuildsParties;

    /**
     * @return array<string, array{string, string, bool, array<string, string>}>
     */
    public static function routes(): array
    {
        return [
            'copy' => [
                'POST',
                'frontend.invoices.copy',
                true,
                [],
            ],
            'delete' => [
                'DELETE',
                'frontend.invoices.destroy',
                false,
                [],
            ],
            'set status' => [
                'PUT',
                'frontend.invoices.set-status',
                true,
                ['status' => 'paid'],
            ],
        ];
    }

    /** @param array<string, string> $data */
    #[Test]
    #[DataProvider('routes')]
    public function a_guest_is_not_authenticated(
        string $method,
        string $route,
        bool $issued,
        array $data,
    ): void {
        $invoice = $this->invoiceOf($this->frontendUser(), $issued);

        $response = $this->json(
            $method,
            $this->url(
                $route,
                $invoice,
                $data,
            ),
            $data,
        );

        $response->assertUnauthorized();
    }

    /** @param array<string, string> $data */
    #[Test]
    #[DataProvider('routes')]
    public function a_user_without_the_permission_is_forbidden(
        string $method,
        string $route,
        bool $issued,
        array $data,
    ): void {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $invoice = $this->invoiceOf($user, $issued);

        $httpClient = $this->actingAs($user, 'web');
        $response = $httpClient->json(
            $method,
            $this->url(
                $route,
                $invoice,
                $data,
            ),
            $data,
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    /** @param array<string, string> $data */
    #[Test]
    #[DataProvider('routes')]
    public function another_users_invoice_is_not_found(
        string $method,
        string $route,
        bool $issued,
        array $data,
    ): void {
        $invoice = $this->invoiceOf($this->frontendUser(), $issued);

        $httpClient = $this->actingAs($this->frontendUser(), 'web');
        $response = $httpClient->json(
            $method,
            $this->url(
                $route,
                $invoice,
                $data,
            ),
            $data,
        );

        $response->assertNotFound();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    /** @param array<string, string> $data */
    #[Test]
    #[DataProvider('routes')]
    public function the_owner_with_the_plan_role_succeeds(
        string $method,
        string $route,
        bool $issued,
        array $data,
    ): void {
        $user = $this->frontendUser();
        $invoice = $this->invoiceOf($user, $issued);

        $httpClient = $this->actingAs($user, 'web');
        $response = $httpClient->call(
            $method,
            $this->url(
                $route,
                $invoice,
                $data,
            ),
            $data,
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
    }

    private function invoiceOf(User $user, bool $issued): Invoice
    {
        $invoiceFactory = Invoice::factory();
        if (!$issued) {
            $invoiceFactory = $invoiceFactory->draft();
        }

        return $invoiceFactory->create(['user_id' => $user->id]);
    }

    /** @param array<string, string> $data */
    private function url(
        string $route,
        Invoice $invoice,
        array $data,
    ): string {
        return route(
            $route,
            [
                ...$data,
                'locale' => 'cs',
                'invoice' => $invoice->id,
            ],
        );
    }
}
