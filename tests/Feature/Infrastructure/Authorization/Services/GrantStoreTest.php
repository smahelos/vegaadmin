<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Authorization\Services;

use App\Application\Product\Commands\CreateProduct;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\User\Commands\AssignRole;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\User\Enums\Permission;
use App\Domain\User\Exceptions\PermissionDeniedException;
use App\Livewire\Product\ProductList;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsParties;
use Tests\TestCase;

/**
 * What an actor may is read once per request and never carried into the next one (D-049, PRD_GRANTS).
 */
final class GrantStoreTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsParties;

    /** @var array<string, int> reads of a user's roles, direct permissions and role limits */
    private array $grantReads = [
        'model_has_roles' => 0,
        'model_has_permissions' => 0,
        'role_limits' => 0,
    ];

    #[Test]
    public function a_request_with_several_checks_and_a_limit_reads_the_grants_once(): void
    {
        $user = $this->frontendUser();
        $this->actingAs($user, 'web');
        $this->countGrantReads();

        // The route's allowed middleware, the gate's run and the product limit all ask
        $response = $this->post(
            route('frontend.product.store', ['locale' => 'cs']),
            [
                'name' => 'Counted',
                'price' => 10,
                'currency' => 'CZK',
            ],
        );

        $response->assertRedirect();
        $productQuery = Product::where('user_id', $user->id);
        $this->assertTrue($productQuery->exists());
        $this->assertSame(
            [
                'model_has_roles' => 1,
                'model_has_permissions' => 1,
                'role_limits' => 1,
            ],
            $this->grantReads,
        );
    }

    #[Test]
    public function a_chart_request_reads_the_grants_once(): void
    {
        $user = $this->frontendUser();
        $this->actingAs($user, 'web');
        $this->countGrantReads();

        $response = $this->getJson(
            route('api.statistics.invoice-status'),
        );

        $response->assertOk();
        $this->assertSame(1, $this->grantReads['model_has_roles']);
        $this->assertSame(1, $this->grantReads['model_has_permissions']);
        // Nothing is created, so no limit is read
        $this->assertSame(0, $this->grantReads['role_limits']);
    }

    #[Test]
    public function a_role_assigned_through_the_gate_applies_to_the_rest_of_the_request(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);
        $actor = Actor::user(
            $this->idOf($user),
        );
        $this->assertFalse(
            $useCases->allowed($actor, CreateProduct::class),
        );

        $useCases->run(
            Actor::system(),
            new AssignRole($user->email, 'frontend_user'),
        );

        $product = $useCases->run(
            $actor,
            new CreateProduct(
                $this->idOf($user),
                ProductWriteData::fromArray(['name' => 'After the role', 'price' => 10]),
            ),
        );
        $this->assertSame('After the role', $product->name);
    }

    #[Test]
    public function a_role_removed_between_two_requests_applies_in_the_second(): void
    {
        $user = $this->frontendUser();
        $this->actingAs($user, 'web');
        $route = route('api.statistics.invoice-status');
        $first = $this->getJson($route);
        $first->assertOk();

        $user->removeRole('frontend_user');

        $second = $this->getJson($route);
        $second->assertForbidden();
    }

    #[Test]
    public function a_role_removed_between_two_livewire_requests_applies_in_the_second(): void
    {
        $user = $this->frontendUser();
        $this->actingAs($user, 'web');
        $component = Livewire::test(ProductList::class);
        $component->assertStatus(200);

        $user->removeRole('frontend_user');

        $component->call('resetFilters');
        $component->assertStatus(403);
    }

    #[Test]
    public function holds_answers_like_allowed_on_the_actors_guard_only(): void
    {
        // frontend_user holds product.create in web; the same user is no admin in backpack
        $user = $this->frontendUser();
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);
        $asUser = Actor::user(
            $this->idOf($user),
        );
        $asAdmin = Actor::admin(
            $this->idOf($user),
        );

        $this->assertTrue(
            $useCases->holds($asUser, Permission::PRODUCT_CREATE),
        );
        $this->assertTrue(
            $useCases->allowed($asUser, CreateProduct::class),
        );
        $this->assertFalse(
            $useCases->holds($asAdmin, Permission::PRODUCT_CREATE),
        );
        $this->assertFalse(
            $useCases->allowed($asAdmin, CreateProduct::class),
        );
        $this->expectException(PermissionDeniedException::class);
        $useCases->run(
            $asAdmin,
            new CreateProduct(
                $this->idOf($user),
                ProductWriteData::fromArray(['name' => 'Refused', 'price' => 10]),
            ),
        );
    }

    #[Test]
    public function the_holds_directive_answers_for_the_admin_also_while_the_default_guard_is_web(): void
    {
        // An admin error page renders the menu before any middleware switched the default guard to backpack
        $template = '@holds(App\Domain\User\Enums\Permission::USER_VIEW, App\Domain\User\Enums\Permission::PRODUCT_CREATE)'
            . ' shown @endholds';
        $admin = $this->backendUser(Permission::USER_VIEW);
        $other = $this->backendUser();
        $user = $this->frontendUser();
        $auth = app('auth');

        $this->actingAs($admin, 'backpack');
        $auth->shouldUse('web');
        $this->assertSame(
            'shown',
            trim(
                Blade::render($template),
            ),
        );
        // The frontend user's product.create on web is no admin permission
        $this->actingAs($other, 'backpack');
        $this->actingAs($user, 'web');
        $this->assertSame(
            '',
            trim(
                Blade::render($template),
            ),
        );
    }

    #[Test]
    public function a_role_assigned_in_a_rolled_back_transaction_does_not_apply(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);
        $actor = Actor::user(
            $this->idOf($user),
        );

        try {
            DB::transaction(
                function () use ($useCases, $user, $actor): void {
                    $useCases->run(
                        Actor::system(),
                        new AssignRole($user->email, 'frontend_user'),
                    );
                    $this->assertTrue(
                        $useCases->allowed($actor, CreateProduct::class),
                    );

                    throw new RuntimeException('Rolled back');
                },
            );
        } catch (RuntimeException) {
        }

        $this->assertFalse(
            $useCases->allowed($actor, CreateProduct::class),
        );
    }

    private function countGrantReads(): void
    {
        DB::listen(
            function (QueryExecuted $query): void {
                foreach (array_keys($this->grantReads) as $table) {
                    if (str_contains($query->sql, '`' . $table . '`')) {
                        $this->grantReads[$table]++;
                    }
                }
            },
        );
    }
}
