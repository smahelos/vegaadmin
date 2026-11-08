<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components\Admin;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoleLimitTest extends TestCase
{
    private const TEMPLATE = '<x-admin.role-limit :field="$field" :editing="$editing" />';

    #[Test]
    public function a_new_limit_starts_in_the_limited_mode(): void
    {
        $view = $this->blade(
            self::TEMPLATE,
            ['field' => ['name' => 'invoices', 'label' => 'Invoices'], 'editing' => false],
        );

        $view->assertSee('<option value="limited" selected', false);
        $view->assertSee('name="invoices" value=""', false);
        $view->assertDontSee(' hidden', false);
    }

    #[Test]
    public function the_old_input_wins_over_the_stored_limit(): void
    {
        $request = Request::create('/admin/role-limit/1/edit');
        $session = $this->app->make('session.store');
        $session->flashInput(['invoices_mode' => 'limited', 'invoices' => '7']);
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);

        $view = $this->blade(
            self::TEMPLATE,
            ['field' => [
                'name' => 'invoices',
                'label' => 'Invoices',
                'value' => null,
            ], 'editing' => true],
        );

        $view->assertSee('<option value="limited" selected', false);
        $view->assertSee('name="invoices" value="7"', false);
    }

    #[Test]
    public function a_stored_unlimited_limit_hides_the_number(): void
    {
        $view = $this->blade(
            self::TEMPLATE,
            ['field' => [
                'name' => 'invoices',
                'label' => 'Invoices',
                'value' => null,
            ], 'editing' => true],
        );

        $view->assertSee('<option value="unlimited" selected', false);
        $view->assertSee('name="invoices" value=""', false);
        $view->assertSee(' hidden', false);
    }
}
