<?php

declare(strict_types=1);

// Two classes use the trait, whose lookup is reported once, in the trait's file
namespace Tests\Unit\Phpstan\Rules\data\app\ContainerLookups;

final class UsesTheTrait
{
    use LooksUpInATrait;
}

final class UsesTheTraitToo
{
    use LooksUpInATrait;
}
