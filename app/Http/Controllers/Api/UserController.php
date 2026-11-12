<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\User\Queries\SearchUsers;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\User\DTO\UserDTO;
use App\Http\Controllers\Admin\ActsAsAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserSearchRequest;
use App\Http\Resources\Admin\UserOptionResource;
use Illuminate\Pagination\LengthAwarePaginator;

final class UserController extends Controller
{
    use ActsAsAdmin;

    private const PER_PAGE = 10;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    /**
     * The owner lookup of admin forms. Backpack's select2_from_ajax reads the paginator's own shape (current_page
     * and last_page at the top level), which a resource collection would move under meta, so each item goes through
     * the resource inside the paginator.
     *
     * @return LengthAwarePaginator<int, UserOptionResource>
     */
    public function search(UserSearchRequest $request): LengthAwarePaginator
    {
        // The route's `allowed` middleware names SearchUsers: change them together
        $found = $this->useCases->run(
            $this->admin(),
            new SearchUsers(
                $request->validated('q'),
                new PageRequest(
                    $request->integer('page', 1),
                    self::PER_PAGE,
                ),
            ),
        );

        return new LengthAwarePaginator(
            array_map(
                fn (UserDTO $user): UserOptionResource => new UserOptionResource($user),
                $found->items(),
            ),
            $found->total(),
            $found->perPage(),
            $found->currentPage(),
            ['path' => $request->url()],
        );
    }
}
