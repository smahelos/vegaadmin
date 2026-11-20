<?php

declare(strict_types=1);

// Stands for application code: the rule test checks this directory like app/
namespace App\ContainerLookups;

use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\App;

final class LooksUp
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function lookups(): void
    {
        $presenter = app(CountryOptionsPresenter::class);
        $application = app();
        $application->getLocale();
        $resolved = resolve(CountryOptionsPresenter::class);
        $made = App::make(CountryOptionsPresenter::class);
        $made = \Illuminate\Support\Facades\App::makeWith(CountryOptionsPresenter::class, []);
        $got = App::get(CountryOptionsPresenter::class);
        App::call([$this, 'state']);
        $aliased = \App::make(CountryOptionsPresenter::class);
        $container = \Illuminate\Container\Container::getInstance();
        $application = \Illuminate\Foundation\Application::getInstance();
    }

    public function state(): void
    {
        App::getLocale();
        $this->container->make(CountryOptionsPresenter::class);
    }
}
