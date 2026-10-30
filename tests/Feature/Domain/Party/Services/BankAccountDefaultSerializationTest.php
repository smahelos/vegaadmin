<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Party\Services;

use App\Application\Party\Services\BankAccountApplicationService;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\User\ValueObjects\UserId;
use App\Models\BankAccount;
use App\Models\Contact;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BankAccountDefaultSerializationTest extends TestCase
{
    #[Test]
    public function a_concurrent_archive_waits_until_the_default_selection_commits(): void
    {
        $connection = $this->secondConnection();
        $connection->prepare("INSERT INTO users (name, email, password, created_at, updated_at) VALUES ('Default owner', ?, '', NOW(), NOW())")
            ->execute(['default-' . uniqid() . '@billex.test']);
        $userId = (int) $connection->lastInsertId();
        $this->beforeApplicationDestroyed(function () use ($connection, $userId): void {
            $connection->exec("DELETE FROM users WHERE id = {$userId}");
        });
        $contact = Contact::factory()->create(['user_id' => $userId]);
        $account = BankAccount::factory()->ownedBy($contact)->create();
        $this->assertNull($this->lockError($connection, $userId));

        app(BankAccountApplicationService::class)->setDefault(UserId::fromInt($userId), BankAccountId::fromInt($account->id));

        $this->assertSame($account->id, $contact->fresh()->default_bank_account_id);
        $this->assertSame(1205, $this->lockError($connection, $userId));
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

    /** Foreign-key shared locks allow this probe; the exclusive owner lock serializes default changes and archive. */
    private function lockError(PDO $connection, int $userId): ?int
    {
        $connection->beginTransaction();
        try {
            $connection->query("SELECT id FROM users WHERE id = {$userId} FOR SHARE");

            return null;
        } catch (PDOException $exception) {
            return $exception->errorInfo[1] ?? null;
        } finally {
            $connection->rollBack();
        }
    }
}
