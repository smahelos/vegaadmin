<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\User\Enums\Permission;
use App\Models\Invoice;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Writing an invoice in the frontend needs frontend.can_create_invoice to create it and
 * frontend.can_update_invoice for every change of a stored one.
 */
final class InvoicePermissionsTest extends TestCase
{
    /**
     * @return array<string, array{string, string, array<string, mixed>}>
     */
    public static function invoiceChanges(): array
    {
        return [
            'update' => [
                'PUT',
                'frontend.invoice.update',
                [],
            ],
            'mark as paid' => [
                'PUT',
                'frontend.invoice.mark-as-paid',
                [],
            ],
            'set template' => [
                'PUT',
                'frontend.invoice.set-template',
                ['template' => 'modern'],
            ],
            'link contact' => [
                'POST',
                'frontend.invoices.link-contact',
                [],
            ],
            'save to contacts' => [
                'POST',
                'frontend.invoices.save-to-contacts',
                [],
            ],
        ];
    }

    #[Test]
    public function creating_an_invoice_needs_the_create_permission(): void
    {
        $user = $this->userWith([Permission::INVOICE_UPDATE]);

        $httpClient = $this->actingAs($user, 'web');
        $response = $httpClient->post(
            route(
                'frontend.invoice.store',
                ['locale' => $this->locale()],
            ),
            [],
        );

        $response->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    #[Test]
    #[DataProvider('invoiceChanges')]
    public function changing_an_invoice_needs_the_update_permission(
        string $method,
        string $route,
        array $parameters,
    ): void {
        $user = $this->userWith([Permission::INVOICE_CREATE]);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'template' => InvoiceTemplate::DEFAULT->value,
            ],
        );

        $httpClient = $this->actingAs($user, 'web');
        $response = $httpClient->call(
            $method,
            route(
                $route,
                [
                    ...$parameters,
                    'locale' => $this->locale(),
                    'id' => $invoice->id,
                    'invoice' => $invoice->id,
                ],
            ),
        );

        $response->assertForbidden();
        $invoice->refresh();
        $this->assertSame(InvoiceTemplate::DEFAULT->value, $invoice->template);
    }

    #[Test]
    public function setting_a_template_outside_the_known_templates_is_rejected(): void
    {
        $user = $this->userWith([Permission::INVOICE_UPDATE]);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'template' => InvoiceTemplate::DEFAULT->value,
            ],
        );

        $httpClient = $this->actingAs($user, 'web');
        $response = $httpClient->put(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => $this->locale(),
                    'id' => $invoice->id,
                    'template' => 'no-such-template',
                ],
            ),
        );

        $response->assertSessionHasErrors('template');
        $invoice->refresh();
        $this->assertSame(InvoiceTemplate::DEFAULT->value, $invoice->template);
    }

    #[Test]
    public function setting_a_template_with_the_update_permission_stores_it(): void
    {
        $user = $this->userWith([Permission::INVOICE_UPDATE]);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'template' => InvoiceTemplate::DEFAULT->value,
            ],
        );

        $httpClient = $this->actingAs($user, 'web');
        $response = $httpClient->put(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => $this->locale(),
                    'id' => $invoice->id,
                    'template' => InvoiceTemplate::MODERN->value,
                ],
            ),
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $invoice->refresh();
        $this->assertSame(InvoiceTemplate::MODERN->value, $invoice->template);
    }

    private function locale(): string
    {
        $application = app();

        return $application->getLocale();
    }

    /**
     * @param  list<Permission>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(
                PermissionModel::findByName($permission->value, 'web'),
            );
        }

        return $user;
    }
}
