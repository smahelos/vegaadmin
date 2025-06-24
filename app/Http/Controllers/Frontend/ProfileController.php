<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Contracts\UserServiceInterface;
use App\Traits\UserFormFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\UserRequest;
use App\Http\Requests\PasswordUpdateRequest;

class ProfileController extends Controller
{
    use UserFormFields;

    /**
     * User service instance
     *
     * @var UserServiceInterface
     */
    protected $userService;

    /**
     * Constructor
     *
     * @param UserServiceInterface $userService
     */
    public function __construct(UserServiceInterface $userService)
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
        try {
            // Get authenticated user
            $user = $this->userService->findUserById(Auth::id());
            
            // Get validated data from request
            $validatedData = $request->validated();
            
            // Update user using service
            $this->userService->updateProfile($user, $validatedData);
            
            return redirect()->route('frontend.profile.edit', ['locale' => $request->input('lang', app()->getLocale())])
                ->with('success', __('users.messages.profile_updated'));
        } catch (\Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => __('users.messages.profile_error_update')]);
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
            
            // Update password using service
            $this->userService->updatePassword($user, $request->password);
            
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
