<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Application\User\Contracts\UserApplicationServiceInterface;
use App\Infrastructure\Forms\User\UserFormFields;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Domain\User\ValueObjects\UserPassword;

class ProfileController extends Controller
{
    use UserFormFields;

    /**
     * User service instance
     *
     * @var \App\Application\User\Contracts\UserApplicationServiceInterface
     */
    protected $userService;

    /**
     * Constructor
     *
     * @param \App\Application\User\Contracts\UserApplicationServiceInterface $userService
     */
    public function __construct(UserApplicationServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Show form for editing user profile
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit()
    {
        try {
            // Get authenticated user as full model instance
            $user = $this->userService->findUserById(Auth::id());

            // Get fields from trait
            $userFields = $this->getUserFields();
            $passwordFields = $this->getPasswordFields();

            return view('frontend.profile.edit', compact('user', 'userFields', 'passwordFields'));
        } catch (\Exception $e) {
            Log::error('Error loading profile: ' . $e->getMessage());

            return redirect()->route('frontend.dashboard', ['locale' => app()->getLocale()])
                ->withErrors(['error' => __('users.messages.profile_error')]);
        }
    }

    /**
     * Update user profile
     *
     * @param UserRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UserRequest $request)
    {
        Log::info('ProfileController::update called', [
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);

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
            Log::error('Error updating profile: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => __('users.messages.profile_error_update') . $e->getMessage()]);
        }
    }

    /**
     * Update only user password
     *
     * @param PasswordUpdateRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(PasswordUpdateRequest $request)
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
            Log::error('Error changing password: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => __('users.messages.profile_error_password_update')]);
        }
    }
}
