<?php

declare(strict_types=1);

// Stands for a migration, which Laravel creates without the container
namespace Database\ContainerLookups;

use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;

final class LooksUpInAMigration
{
    public function up(): void
    {
        $presenter = app(CountryOptionsPresenter::class);
    }
}
