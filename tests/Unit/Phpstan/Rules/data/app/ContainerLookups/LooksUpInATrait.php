<?php

declare(strict_types=1);

// Stands for a trait of application code, analysed in each class that uses it; PHPStan finds it through the
// autoload-dev namespace of tests/
namespace Tests\Unit\Phpstan\Rules\data\app\ContainerLookups;

trait LooksUpInATrait
{
    public function locale(): string
    {
        $application = app();

        return $application->getLocale();
    }
}
