<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class BankCrudControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAdminTestEnvironment;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function create_form_uses_bank_form_fields(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $response = $this->get('/admin/bank/create');

        $response->assertOk();
        $response->assertSee(__('banks.fields.name'));
        $response->assertSee(__('banks.hints.code'));
        // Country is a select of ISO codes defaulting to CZ, not a free text input
        $response->assertSee('<option value="CZ"', false);
    }

    #[Test]
    public function store_persists_bank(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $code = substr(uniqid(), -6);

        $response = $this->post('/admin/bank', [
            'name' => 'Test Bank ' . $code,
            'code' => $code,
            'swift' => 'TESTCZPP',
            'country' => 'CZ',
            'active' => 1,
            'description' => 'Created from test',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('banks', ['code' => $code, 'country' => 'CZ']);
    }
}
