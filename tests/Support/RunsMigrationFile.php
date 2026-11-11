<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Migrations\Migration;
use LogicException;

/**
 * Runs one step of a migration file, which returns an anonymous class that only the file itself names.
 */
trait RunsMigrationFile
{
    private function migrateUp(string $file): void
    {
        $migration = require database_path('migrations/' . $file);
        if (!$migration instanceof Migration || !method_exists($migration, 'up')) {
            throw new LogicException('The file holds no migration with up(): ' . $file);
        }

        $migration->up();
    }

    private function migrateDown(string $file): void
    {
        $migration = require database_path('migrations/' . $file);
        if (!$migration instanceof Migration || !method_exists($migration, 'down')) {
            throw new LogicException('The file holds no migration with down(): ' . $file);
        }

        $migration->down();
    }
}
