<?php

declare(strict_types=1);

namespace Tests\Unit\Phpstan\Rules\data\ScopeIssued;

use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\ValueObjects\UserId;

final class IssuesScopes
{
    public function write(UserId $owner): WriteScope
    {
        $read = ReadScope::issue($owner);

        return WriteScope::issue($owner, $read);
    }
}
