<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\User\Services;

use App\Application\Party\Commands\CreateBusiness;
use App\Application\Party\Commands\CreateContact;
use App\Application\Party\Commands\RestoreBusiness;
use App\Application\Party\Commands\RestoreContact;
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
use Tests\Support\RunsUseCases;
use Tests\TestCase;

final class EntityLimitLockingTest extends TestCase
{
    use BuildsParties;
    use RunsUseCases;

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
        $userQuery = User::query();
        $user = $userQuery->findOrFail($ownerId);
        $user->assignRole('frontend_user');
        $probe = "SELECT id FROM users WHERE id = {$ownerId} FOR SHARE";
        $this->assertNull(
            $this->lockError($other, $probe),
            'The owner was locked before the capacity check',
        );

        $this->runAs(
            UserId::fromInt($ownerId),
            new CreateContact(
                UserId::fromInt($ownerId),
                new ContactWriteData($this->profile(), null),
            ),
        );

        $this->assertSame(
            self::LOCK_WAIT_TIMEOUT,
            $this->lockError($other, $probe),
        );
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
        $limitQuery = $other->prepare(
            'SELECT role_limits.' . $table . ' FROM role_limits JOIN roles ON roles.id = role_limits.role_id'
            . ' WHERE roles.name = ? AND roles.guard_name = ?',
        );
        $limitQuery->execute(['frontend_user', 'web']);
        $limit = (int) $limitQuery->fetchColumn();
        $this->assertGreaterThan(0, $limit);
        $insert = $other->prepare(
            'INSERT INTO ' . $table . ' (user_id, name, archived_at) VALUES (?, ?, ?)',
        );
        $archived = [];
        for ($i = 0; $i <= $limit; $i++) {
            $isArchived = $i >= $limit - 1;
            $insert->execute(
                [
                    $ownerId,
                    'Capacity candidate ' . $i,
                    $isArchived ? '2026-01-01 00:00:00' : null,
                ],
            );
            if ($isArchived) {
                $archived[] = (int) $other->lastInsertId();
            }
        }
        $userQuery = User::query();
        $user = $userQuery->findOrFail($ownerId);
        $user->assignRole('frontend_user');
        $activeQuery = DB::table($table);
        $activeQuery->where('user_id', $ownerId);
        $activeQuery->whereNull('archived_at');
        $this->assertSame(
            $limit - 1,
            $activeQuery->count(),
        );

        // The competing request commits after this transaction established its consistent-read snapshot.
        $other->beginTransaction();
        $lockStatement = $other->query(
            'SELECT id FROM users WHERE id = ' . $ownerId . ' FOR UPDATE',
        );
        $lockStatement->fetchAll();
        $other->exec(
            'UPDATE ' . $table . ' SET archived_at = NULL WHERE id = ' . $archived[1],
        );
        $other->commit();

        $this->expectException(EntityLimitExceededException::class);
        $userId = UserId::fromInt($ownerId);
        if ($business) {
            if ($restore) {
                $this->runAs(
                    $userId,
                    new RestoreBusiness(
                        $userId,
                        BusinessId::fromInt($archived[0]),
                    ),
                );
            } else {
                $this->runAs(
                    $userId,
                    new CreateBusiness(
                        $userId,
                        new BusinessWriteData($this->profile(), null),
                        null,
                    ),
                );
            }
        } elseif ($restore) {
            $this->runAs(
                $userId,
                new RestoreContact(
                    $userId,
                    ContactId::fromInt($archived[0]),
                ),
            );
        } else {
            $this->runAs(
                $userId,
                new CreateContact(
                    $userId,
                    new ContactWriteData($this->profile(), null),
                ),
            );
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
        $insert = $other->prepare(
            'INSERT INTO businesses (user_id, name, archived_at) VALUES (?, ?, ?)',
        );
        $insert->execute(
            [
                $ownerId,
                'Archived business',
                '2026-01-01 00:00:00',
            ],
        );
        $archivedId = (int) $other->lastInsertId();
        $userQuery = User::query();
        $user = $userQuery->findOrFail($ownerId);
        $user->assignRole('frontend_user');
        $businessesQuery = DB::table('businesses');
        $businessesQuery->where('user_id', $ownerId);
        $businessesQuery->whereNull('archived_at');
        $this->assertSame(
            0,
            $businessesQuery->count(),
        );

        $other->beginTransaction();
        $lockStatement = $other->query(
            'SELECT id FROM users WHERE id = ' . $ownerId . ' FOR UPDATE',
        );
        $lockStatement->fetchAll();
        $insert->execute([
            $ownerId,
            'Concurrent first business',
            null,
        ]);
        $defaultId = (int) $other->lastInsertId();
        $other->exec(
            'UPDATE users SET default_business_id = ' . $defaultId . ' WHERE id = ' . $ownerId,
        );
        $other->commit();

        $userId = UserId::fromInt($ownerId);
        if ($restore) {
            $this->runAs(
                $userId,
                new RestoreBusiness(
                    $userId,
                    BusinessId::fromInt($archivedId),
                ),
            );
        } else {
            $this->runAs(
                $userId,
                new CreateBusiness(
                    $userId,
                    new BusinessWriteData($this->profile(), null),
                    null,
                ),
            );
        }

        $usersQuery = DB::table('users');
        $usersQuery->where('id', $ownerId);
        $usersQuery->lockForUpdate();
        $this->assertSame(
            $defaultId,
            $usersQuery->value('default_business_id'),
        );
    }

    private function secondConnection(): PDO
    {
        $config = config(
            'database.connections.' . config('database.default'),
        );
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
        $statement = $connection->prepare(
            "INSERT INTO users (name, email, password, created_at, updated_at) VALUES ('Capacity owner', ?, '', NOW(), NOW())",
        );
        $statement->execute(['capacity-' . uniqid() . '@billex.test']);
        $id = (int) $connection->lastInsertId();

        $this->beforeApplicationDestroyed(
            function () use ($connection, $id): void {
                $connection->exec(
                    "UPDATE users SET default_business_id = NULL WHERE id = {$id}",
                );
                $connection->exec("DELETE FROM contacts WHERE user_id = {$id}");
                $connection->exec("DELETE FROM businesses WHERE user_id = {$id}");
                $connection->exec("DELETE FROM users WHERE id = {$id}");
            },
        );

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
