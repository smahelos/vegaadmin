<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\User\Services;

use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Exceptions\AmbiguousPlanRoleException;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\Services\EntityLimit;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\RoleLimit;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Support\Fakes\Shared\FixedClock;
use Tests\TestCase;

final class EntityLimitTest extends TestCase
{
    private const NOW = '2026-11-17 10:00:00 UTC';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            ClockInterface::class,
            new FixedClock(
                new DateTimeImmutable(self::NOW),
            ),
        );
    }

    /**
     * @return array<string, array{LimitedEntity}>
     */
    public static function limitedEntities(): array
    {
        $entities = [];
        foreach (LimitedEntity::cases() as $entity) {
            $entities[$entity->value] = [$entity];
        }

        return $entities;
    }

    #[Test]
    #[DataProvider('limitedEntities')]
    public function a_role_without_limits_allows_no_limited_creation(LimitedEntity $entity): void
    {
        $roleQuery = Role::query();
        $role = $roleQuery->create(['name' => 'no_limits_' . uniqid(), 'guard_name' => 'web']);
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->assignRole($role);

        $this->assertRefused($owner, $entity);
    }

    #[Test]
    public function a_zero_limit_refuses_creation(): void
    {
        $owner = $this->ownerWithLimits(['businesses' => 0]);

        $this->assertRefused($owner, LimitedEntity::BUSINESS);
    }

    #[Test]
    public function an_unlimited_value_allows_any_number(): void
    {
        $owner = $this->ownerWithLimits(['invoices' => null]);
        $invoiceFactory = Invoice::factory(3);
        $invoiceFactory->create(['user_id' => $owner->id]);

        $this->assertAllowed($owner, LimitedEntity::INVOICE);
    }

    #[Test]
    public function refuses_the_invoice_over_the_monthly_quota(): void
    {
        $owner = $this->ownerWithLimits(['invoices' => 2]);
        $invoiceFactory = Invoice::factory(2);
        $invoiceFactory->create(
            ['user_id' => $owner->id, 'created_at' => '2026-11-02 08:00:00'],
        );

        $this->assertRefused($owner, LimitedEntity::INVOICE);
    }

    #[Test]
    public function the_month_starts_at_midnight_local_time(): void
    {
        $owner = $this->ownerWithLimits(['invoices' => 1]);
        $invoiceFactory = Invoice::factory();
        // 23:59 on 31 October in Prague
        $invoiceFactory->create(
            ['user_id' => $owner->id, 'created_at' => '2026-10-31 22:59:59'],
        );
        $this->assertAllowed($owner, LimitedEntity::INVOICE);

        // 00:30 on 1 November in Prague
        $invoiceFactory->create(
            ['user_id' => $owner->id, 'created_at' => '2026-10-31 23:30:00'],
        );
        $this->assertRefused($owner, LimitedEntity::INVOICE);
    }

    #[Test]
    public function the_quota_window_starts_at_the_plan_role_assignment(): void
    {
        $owner = $this->ownerWithLimits(['invoices' => 1]);
        $owner->forceFill(['plan_role_assigned_at' => '2026-11-10 12:00:00']);
        $owner->save();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            ['user_id' => $owner->id, 'created_at' => '2026-11-10 11:59:59'],
        );
        $this->assertAllowed($owner, LimitedEntity::INVOICE);

        $invoiceFactory->create(
            ['user_id' => $owner->id, 'created_at' => '2026-11-10 12:00:00'],
        );
        $this->assertRefused($owner, LimitedEntity::INVOICE);
    }

    #[Test]
    public function deleting_an_expense_frees_its_place_in_the_quota(): void
    {
        $owner = $this->ownerWithLimits(['expenses' => 1]);
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create(
            ['user_id' => $owner->id, 'created_at' => '2026-11-03 09:00:00'],
        );
        $this->assertRefused($owner, LimitedEntity::EXPENSE);

        $expense->delete();

        $this->assertAllowed($owner, LimitedEntity::EXPENSE);
    }

    /**
     * @return array<string, array{LimitedEntity, class-string<Business|Contact>}>
     */
    public static function parties(): array
    {
        return [
            'business' => [LimitedEntity::BUSINESS, Business::class],
            'contact' => [LimitedEntity::CONTACT, Contact::class],
        ];
    }

    /**
     * @param  class-string<Business|Contact>  $model
     */
    #[Test]
    #[DataProvider('parties')]
    public function archiving_frees_a_capacity_place(
        LimitedEntity $entity,
        string $model,
    ): void {
        $owner = $this->ownerWithLimits([$entity->value => 2]);
        $archivedFactory = $model::factory();
        $archivedFactory = $archivedFactory->archived();
        $archivedFactory->create(['user_id' => $owner->id]);
        $partyFactory = $model::factory(2);
        $parties = $partyFactory->create(['user_id' => $owner->id]);
        $this->assertRefused($owner, $entity);

        $parties[0]->update(['archived_at' => self::NOW]);

        $this->assertAllowed($owner, $entity);
    }

    #[Test]
    public function every_product_takes_a_capacity_place(): void
    {
        $owner = $this->ownerWithLimits(['products' => 1]);
        $productFactory = Product::factory();
        $productFactory->create(
            ['user_id' => $owner->id, 'created_at' => '2025-01-01 00:00:00'],
        );

        $this->assertRefused($owner, LimitedEntity::PRODUCT);
    }

    #[Test]
    public function usage_reports_the_count_and_the_limit_the_check_compares(): void
    {
        $owner = $this->ownerWithLimits(['invoices' => 2, 'products' => null]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            ['user_id' => $owner->id, 'created_at' => '2026-11-02 08:00:00'],
        );
        $invoiceFactory->create(
            ['user_id' => $owner->id, 'created_at' => '2026-10-30 08:00:00'],
        );
        $limits = $this->limits();
        $ownerId = UserId::fromInt($owner->id);

        $invoices = $limits->usage($ownerId, LimitedEntity::INVOICE);
        $products = $limits->usage($ownerId, LimitedEntity::PRODUCT);

        $this->assertSame(1, $invoices->used);
        $this->assertSame(2, $invoices->limit);
        $this->assertSame(0, $products->used);
        $this->assertNull($products->limit);
    }

    #[Test]
    public function showing_the_usage_takes_no_locks(): void
    {
        $owner = $this->ownerWithLimits(['invoices' => 2, 'contacts' => 2]);
        $limits = $this->limits();
        $ownerId = UserId::fromInt($owner->id);
        DB::enableQueryLog();

        $limits->usage($ownerId, LimitedEntity::INVOICE);
        $limits->usage($ownerId, LimitedEntity::CONTACT);

        $statements = array_column(DB::getQueryLog(), 'query');
        $this->assertNotEmpty($statements);
        $this->assertStringNotContainsString(
            'for update',
            implode(' ', $statements),
        );
    }

    #[Test]
    public function refuses_a_user_with_several_plan_roles(): void
    {
        $owner = $this->ownerWithLimits(['contacts' => null]);
        $second = $this->ownerWithLimits(['contacts' => null]);
        $owner->assignRole($second->roles);

        $this->expectException(AmbiguousPlanRoleException::class);

        $limits = $this->limits();
        $limits->assertCanCreate(
            UserId::fromInt($owner->id),
            LimitedEntity::CONTACT,
        );
    }

    /**
     * A user with a new web role whose limit row holds the given values; the others are not allowed (0).
     *
     * @param  array<string, ?int>  $limits
     */
    private function ownerWithLimits(array $limits): User
    {
        $roleQuery = Role::query();
        $role = $roleQuery->create(['name' => 'plan_' . uniqid(), 'guard_name' => 'web']);
        $roleLimitQuery = RoleLimit::query();
        $roleLimitQuery->create(
            $limits + [
                'role_id' => $role->id,
                'invoices' => 0,
                'expenses' => 0,
                'businesses' => 0,
                'contacts' => 0,
                'products' => 0,
            ],
        );
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->assignRole($role);

        return $owner;
    }

    private function assertAllowed(User $owner, LimitedEntity $entity): void
    {
        $limits = $this->limits();
        $limits->assertCanCreate(
            UserId::fromInt($owner->id),
            $entity,
        );
        $this->addToAssertionCount(1);
    }

    private function assertRefused(User $owner, LimitedEntity $entity): void
    {
        $limits = $this->limits();

        try {
            $limits->assertCanCreate(
                UserId::fromInt($owner->id),
                $entity,
            );
        } catch (EntityLimitExceededException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail("Creating {$entity->value} was allowed");
    }

    private function limits(): EntityLimit
    {
        return $this->app->make(EntityLimit::class);
    }
}
