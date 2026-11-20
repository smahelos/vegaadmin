<?php

declare(strict_types=1);

// Stands for application code listed in the rule's files after its conversion
namespace App\ContainerLookups;

use Illuminate\Support\Facades\App;

final class LooksNothingUp
{
    public function locale(): string
    {
        return App::getLocale();
    }
}
