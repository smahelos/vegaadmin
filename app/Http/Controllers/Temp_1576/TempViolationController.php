<?php
namespace App\Http\Controllers\Temp;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;

class TempViolationController
{
    public function __construct(UniversalLimitService $service) {}
}