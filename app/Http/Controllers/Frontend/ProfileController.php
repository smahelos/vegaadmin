<?php

namespace App\Http\Controllers\Frontend;

use App\Application\User\Contracts\UserApplicationServiceInterface;
use App\Domain\User\ValueObjects\UserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\UserRequest;
use App\Infrastructure\Forms\User\UserFormFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use UserFormFields;

    /**
     * Constructor
     */
    public function __construct(private readonly UserApplicationServiceInterface $userService) {}

    /**
     * Show form for editing user profile
     */
    public function edit(): View|RedirectResponse
    {
        try {
            // Get authenticated user as full model instance
            $user = $this->userService->findUserById(Auth::id());

            // Get fields from trait
            $userFields = $this->getUserFields();
            $passwordFields = $this->getPasswordFields();

            return view('frontend.profile.edit', compact('user', 'userFields', 'passwordFields'));
        } catch (\Exception $e) {
            Log::error('Profile loading failed', ['userId' => Auth::id(), 'exception' => $e]);

            return redirect()->route('frontend.dashboard', ['locale' => app()->getLocale()])
                ->withErrors(['error' => __('users.messages.profile_load_failed')]);
        }
    }

    /**
     * Update user profile
     */
    public function update(UserRequest $request): RedirectResponse
    {
        try {
            // Get authenticated user
            $user = $this->userService->findUserById(Auth::id());

            // Get validated data from request
            $validatedData = $request->validated();

            // Update user using service
            $this->userService->updateProfile($user->id, $validatedData);

            return redirect()->route('frontend.profile.edit', ['locale' => app()->getLocale()])
                ->with('success', __('users.messages.profile_updated'));
        } catch (\Exception $e) {
            Log::error('Profile update failed', ['userId' => Auth::id(), 'exception' => $e]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => __('users.messages.profile_update_failed')]);
        }
    }

    /**
     * Update only user password
     */
    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        try {
            // Get authenticated user
            $user = $this->userService->findUserById(Auth::id());

            // Wrap new password in value object for application service
            $newPassword = UserPassword::fromPlainText($request->password);
            $this->userService->updatePassword($user->id, $newPassword);

            return redirect()->route('frontend.profile.edit', ['locale' => $request->input('locale', app()->getLocale())])
                ->with('success', __('users.messages.password_updated'));
        } catch (\Exception $e) {
            Log::error('Password change failed', ['userId' => Auth::id(), 'exception' => $e]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => __('users.messages.password_update_failed')]);
        }
    }
}
