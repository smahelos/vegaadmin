<?php

declare(strict_types=1);

// Stands for application code listed in the rule's files
namespace App\ContainerLookups;

final class StillLooksUp
{
    public function locale(): string
    {
        $application = app();

        return $application->getLocale();
    }
}
