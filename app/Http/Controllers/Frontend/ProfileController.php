<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Traits\UserFormFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\UserRequest;
use App\Http\Requests\PasswordUpdateRequest;

class ProfileController extends Controller
{
    use UserFormFields;
    
    /**
     * Show form for editing user profile
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit()
    {
        try {
            // Get authenticated user as full model instance
            $user = User::findOrFail(Auth::id());
            
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
            // Get authenticated user as full model instance
            $user = User::findOrFail(Auth::id());
            
            // Get validated data from request
            $validatedData = $request->validated();
            
            // Update user
            $user->update($validatedData);
            
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
            // Get validated data
            $validatedData = $request->validated();
            
            $user = User::findOrFail(Auth::id());
            $user->password = Hash::make($request->password);
            $user->save();
            
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
