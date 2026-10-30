<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Party\Services;

use App\Application\Party\Services\BusinessApplicationService;
use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class CapacityLimitTest extends TestCase
{
    use BuildsParties;

    private const LOCK_WAIT_TIMEOUT = 1205;

    /**
     * A second request for the owner's last free place waits for the first one's transaction. The owner is
     * committed by a second connection: a row inserted in the uncommitted test transaction would make any locking
     * read wait on the insert itself. The probe reads with a shared lock, which the foreign key checks of the test
     * transaction allow and only the capacity lock refuses.
     */
    #[Test]
    public function creating_a_contact_locks_its_owner_until_the_transaction_ends(): void
    {
        $other = $this->secondConnection();
        $ownerId = $this->committedUser($other);
        User::query()->findOrFail($ownerId)->assignRole('frontend_user');
        $probe = "SELECT id FROM users WHERE id = {$ownerId} FOR SHARE";
        $this->assertNull($this->lockError($other, $probe), 'The owner was locked before the capacity check');

        $this->app->make(ContactApplicationService::class)->create(
            UserId::fromInt($ownerId),
            new ContactWriteData($this->profile(), null),
        );

        $this->assertSame(self::LOCK_WAIT_TIMEOUT, $this->lockError($other, $probe));
    }

    /** @return iterable<string, array{bool, bool}> */
    public static function activations(): iterable
    {
        yield 'create contact' => [false, false];
        yield 'restore contact' => [false, true];
        yield 'create business' => [true, false];
        yield 'restore business' => [true, true];
    }

    #[Test]
    #[DataProvider('activations')]
    public function activation_checks_the_committed_capacity_after_an_earlier_snapshot(bool $business, bool $restore): void
    {
        $other = $this->secondConnection();
        $ownerId = $this->committedUser($other);
        $table = $business ? 'businesses' : 'contacts';
        $permission = $business ? 'limit_active_businesses_basic' : 'limit_active_contacts_basic';
        $limitQuery = $other->prepare('SELECT limit_value FROM entity_limits WHERE permission_name = ? AND is_active = 1');
        $limitQuery->execute([$permission]);
        $limit = (int) $limitQuery->fetchColumn();
        $this->assertGreaterThan(0, $limit);
        $insert = $other->prepare('INSERT INTO ' . $table . ' (user_id, name, archived_at) VALUES (?, ?, ?)');
        $archived = [];
        for ($i = 0; $i <= $limit; $i++) {
            $isArchived = $i >= $limit - 1;
            $insert->execute([
                $ownerId,
                'Capacity candidate ' . $i,
                $isArchived ? '2026-01-01 00:00:00' : null,
            ]);
            if ($isArchived) {
                $archived[] = (int) $other->lastInsertId();
            }
        }
        User::query()->findOrFail($ownerId)->assignRole('frontend_user');
        $this->assertSame($limit - 1, DB::table($table)->where('user_id', $ownerId)->whereNull('archived_at')->count());

        // The competing request commits after this transaction established its consistent-read snapshot.
        $other->beginTransaction();
        $other->query('SELECT id FROM users WHERE id = ' . $ownerId . ' FOR UPDATE')->fetchAll();
        $other->exec('UPDATE ' . $table . ' SET archived_at = NULL WHERE id = ' . $archived[1]);
        $other->commit();

        $this->expectException(EntityLimitExceededException::class);
        $userId = UserId::fromInt($ownerId);
        if ($business) {
            $service = $this->app->make(BusinessApplicationService::class);
            if ($restore) {
                $service->restore($userId, BusinessId::fromInt($archived[0]));
            } else {
                $service->create(
                    $userId,
                    new BusinessWriteData($this->profile(), null),
                    null,
                );
            }
        } else {
            $service = $this->app->make(ContactApplicationService::class);
            if ($restore) {
                $service->restore($userId, ContactId::fromInt($archived[0]));
            } else {
                $service->create($userId, new ContactWriteData($this->profile(), null));
            }
        }
    }

    /** @return iterable<string, array{bool}> */
    public static function businessActivations(): iterable
    {
        yield 'create' => [false];
        yield 'restore' => [true];
    }

    #[Test]
    #[DataProvider('businessActivations')]
    public function an_earlier_empty_snapshot_does_not_replace_a_concurrently_selected_default(bool $restore): void
    {
        $other = $this->secondConnection();
        $ownerId = $this->committedUser($other);
        $insert = $other->prepare('INSERT INTO businesses (user_id, name, archived_at) VALUES (?, ?, ?)');
        $insert->execute([
            $ownerId,
            'Archived business',
            '2026-01-01 00:00:00',
        ]);
        $archivedId = (int) $other->lastInsertId();
        User::query()->findOrFail($ownerId)->assignRole('frontend_user');
        $this->assertSame(0, DB::table('businesses')->where('user_id', $ownerId)->whereNull('archived_at')->count());

        $other->beginTransaction();
        $other->query('SELECT id FROM users WHERE id = ' . $ownerId . ' FOR UPDATE')->fetchAll();
        $insert->execute([
            $ownerId,
            'Concurrent first business',
            null,
        ]);
        $defaultId = (int) $other->lastInsertId();
        $other->exec('UPDATE users SET default_business_id = ' . $defaultId . ' WHERE id = ' . $ownerId);
        $other->commit();

        $service = $this->app->make(BusinessApplicationService::class);
        $userId = UserId::fromInt($ownerId);
        if ($restore) {
            $service->restore($userId, BusinessId::fromInt($archivedId));
        } else {
            $service->create(
                $userId,
                new BusinessWriteData($this->profile(), null),
                null,
            );
        }

        $this->assertSame($defaultId, DB::table('users')->where('id', $ownerId)->lockForUpdate()->value('default_business_id'));
    }

    private function secondConnection(): PDO
    {
        $config = config('database.connections.' . config('database.default'));
        $connection = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $connection->exec('SET SESSION innodb_lock_wait_timeout = 1');

        return $connection;
    }

    /**
     * The owner outlives the test transaction, so the second connection deletes it once RefreshDatabase has rolled
     * the test back.
     */
    private function committedUser(PDO $connection): int
    {
        $connection
            ->prepare("INSERT INTO users (name, email, password, created_at, updated_at) VALUES ('Capacity owner', ?, '', NOW(), NOW())")
            ->execute(['capacity-' . uniqid() . '@billex.test']);
        $id = (int) $connection->lastInsertId();

        $this->beforeApplicationDestroyed(function () use ($connection, $id): void {
            $connection->exec("UPDATE users SET default_business_id = NULL WHERE id = {$id}");
            $connection->exec("DELETE FROM contacts WHERE user_id = {$id}");
            $connection->exec("DELETE FROM businesses WHERE user_id = {$id}");
            $connection->exec("DELETE FROM users WHERE id = {$id}");
        });

        return $id;
    }

    private function lockError(PDO $connection, string $query): ?int
    {
        $connection->beginTransaction();
        try {
            $connection->query($query);

            return null;
        } catch (PDOException $e) {
            return $e->errorInfo[1] ?? null;
        } finally {
            $connection->rollBack();
        }
    }
}
