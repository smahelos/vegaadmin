<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\UserFormFields;

class RegisterController extends Controller
{
    use UserFormFields;

    /**
     * Show registration form with user fields
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        $userFields = $this->getUserFields();
        $passwordFields = $this->getUserFields();

        return view('auth.register', [
            'userFields' => $userFields,
            'passwordFields' => $passwordFields,
        ]);
    }

    /**
     * Register a new user with related supplier
     *
     * @param RegistrationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(RegistrationRequest $request)
    {
        $validatedData = $request->validated();
        
        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // Create default supplier for new user
            $supplier = new Supplier([
                'name' => $request->name,
                'email' => $request->email,
                'street' => $request->street,
                'city' => $request->city,
                'zip' => $request->zip,
                'country' => $request->country ?? 'CZ',
                'ico' => $request->ico,
                'dic' => $request->dic,
                'phone' => $request->phone,
                'description' => $request->description,
                'is_default' => true,
                'user_id' => $user->id,
                'account_number' => $request->account_number,
                'bank_code' => $request->bank_code,
                'iban' => $request->iban,
                'swift' => $request->swift,
                'bank_name' => $request->bank_name,
                'has_payment_info' => (!empty($request->account_number) && !empty($request->bank_code)),
            ]);
                
            $supplier->save();
        
            event(new Registered($user));
        
            Auth::login($user);
        
            return redirect()->route('home', ['lang' => app()->getLocale()])->with('success', __('auth.registration_success'));
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage(), [
                'email' => $validatedData['email'] ?? 'unknown',
                'ip' => $request->ip()
            ]);
            
            // If error occurs, try to delete partially created user to avoid data inconsistency
            if (isset($user) && $user->exists) {
                $user->delete();
            }
            
            return back()->withInput()->with('error', __('auth.registration_failed'));
        }
    }
}