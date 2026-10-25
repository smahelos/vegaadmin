<?php

declare(strict_types=1);

namespace Tests;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    /**
     * Migrate and seed DatabaseSeeder once per process; every test then runs in a transaction.
     */
    protected bool $seed = true;

    private bool $implicitCommitAllowed = false;

    protected function setUp(): void
    {
        parent::setUp();

        // A test never reaches a real external service; fake the request with Http::fake()
        Http::preventStrayRequests();

        // Built assets (public/build) are not versioned and CI does not run the Vite build
        $this->withoutVite();

        // Disable middleware that might cause issues during tests
        $this->withoutMiddleware(SetLocale::class);

        // Reset Spatie\Permission cached permissions to avoid memory bloat across long test runs
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * RefreshDatabase migrates and seeds the database again before the next test when a test ends
     * outside its transaction (an implicit commit such as OPTIMIZE TABLE or TRUNCATE, a manual
     * rollback, a disconnect), which costs seconds per test.
     */
    protected function tearDown(): void
    {
        // A test that already failed keeps its own message, even when it lost the transaction on the way
        $failed = $this->status()->isFailure() || $this->status()->isError();
        $leftTransaction = ! $failed
            && isset($this->app)
            && ! $this->app->make('db')->connection()->getPdo()->inTransaction();

        parent::tearDown();

        if ($leftTransaction && ! $this->implicitCommitAllowed) {
            $this->fail('The test ended outside the RefreshDatabase transaction, so the next test would migrate and seed again');
        }
    }

    /**
     * For a test of a statement MySQL commits implicitly; the next test migrates and seeds again.
     */
    protected function allowImplicitCommit(): void
    {
        $this->implicitCommitAllowed = true;
    }

    /**
     * RefreshDatabase wipes the database, so refuse anything but the test database — a cached
     * config or a missing phpunit.xml variable would otherwise point it at the development data.
     */
    protected function beforeRefreshingDatabase(): void
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (preg_match('/_testing(_test_\d+)?$/', $database) !== 1) {
            throw new RuntimeException('Tests run only against a *_testing database, not "' . $database . '"');
        }
    }
}
