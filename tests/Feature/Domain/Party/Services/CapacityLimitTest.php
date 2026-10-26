<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Party\Services;

use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;
use PDO;
use PDOException;
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
