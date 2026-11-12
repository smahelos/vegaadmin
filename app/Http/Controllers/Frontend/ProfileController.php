<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\User\Commands\ChangePassword;
use App\Application\User\Commands\UpdateProfile;
use App\Application\User\Queries\GetProfile;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\UserRequest;
use App\Infrastructure\Forms\User\UserFormFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The signed-in user's own profile and password. The locale is the first route parameter of every frontend page.
 */
final class ProfileController extends Controller
{
    use UserFormFields;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    public function edit(): View
    {
        $owner = $this->owner();
        $user = $this->useCases->run(
            Actor::user($owner),
            new GetProfile($owner),
        );

        return view(
            'frontend.profile.edit',
            [
                'user' => $user,
                'userFields' => $this->getUserFields(),
                'passwordFields' => $this->getPasswordFields(),
            ],
        );
    }

    public function update(UserRequest $request): RedirectResponse
    {
        $owner = $this->owner();
        // The route's `allowed` middleware names UpdateProfile: change them together
        $this->useCases->run(
            Actor::user($owner),
            new UpdateProfile(
                $owner,
                (string) $request->string('name'),
                (string) $request->string('email'),
            ),
        );

        return $this->backToProfile('users.messages.profile_updated');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $owner = $this->owner();
        // The route's `allowed` middleware names ChangePassword: change them together
        $this->useCases->run(
            Actor::user($owner),
            new ChangePassword(
                $owner,
                UserPassword::fromPlainText(
                    (string) $request->string('password'),
                ),
            ),
        );

        return $this->backToProfile('users.messages.password_updated');
    }

    private function backToProfile(string $message): RedirectResponse
    {
        $redirector = redirect();
        $redirect = $redirector->route(
            'frontend.profile.edit',
            ['locale' => App::getLocale()],
        );

        return $redirect->with(
            'success',
            __($message),
        );
    }

    private function owner(): UserId
    {
        return UserId::fromInt((int) Auth::id());
    }
}
