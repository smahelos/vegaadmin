<?php

declare(strict_types=1);

// Stands for a composition root in app/Providers, which works with the container
namespace App\Providers\ContainerLookups;

use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;

final class ContainerLookupServiceProvider
{
    public function presenter(): CountryOptionsPresenter
    {
        return app(CountryOptionsPresenter::class);
    }
}
