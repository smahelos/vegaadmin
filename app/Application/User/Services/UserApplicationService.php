<?php

namespace App\Application\User\Services;

use App\Application\User\Contracts\UserApplicationServiceInterface;
use App\Domain\User\Contracts\UserServiceInterface;
use App\Domain\User\ValueObjects\UserPassword;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Infrastructure\Persistence\Eloquent\User\Adapters\PaginatorAdapter;

class UserApplicationService implements UserApplicationServiceInterface
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {
    }

    public function findUserById(int $userId): ?UserDTO
    {
        return $this->userService->findUserById($userId);
    }

    public function getAllUsers():Collection
    {
        return collect($this->userService->getAllUsers());
    }

    public function searchUsers(?string $term, int $perPage = 10): LengthAwarePaginator
    {
        // Build domain pagination request (page is fixed to 1 for admin search endpoint; controller can extend later)
        $page = new PageRequest(1, $perPage);
        $result = $this->userService->searchUsers($term, $page);
        // Convert domain pagination result to Laravel paginator for controllers/UI
        return PaginatorAdapter::toLengthAwarePaginator($result);
    }

    public function updateProfile(int $userId, array $data): bool
    {
        return $this->userService->updateProfile($userId, $data);
    }

    public function updatePassword(int $userId, UserPassword $data): bool
    {
        return $this->userService->updatePassword($userId, $data);
    }
}
