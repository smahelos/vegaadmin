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
use App\Models\Bank;

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
        $passwordFields = $this->getPasswordFields();

        // Get banks for dropdown
        $banks = Bank::where('country', 'CZ')
            ->orderBy('created_at', 'desc')
            ->get()->toArray();
            
        // Banks dropdown
        $banks = $this->getBanksForDropdown();

        // Get banksData for JD bank-fields.js
        $banksData = $this->getBanksForJs();

        return view('auth.register', [
            'userFields' => $userFields,
            'passwordFields' => $passwordFields,
            'banks' => $banks,
            'banksData' => $banksData,
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

    /**
     * Get list of banks with codes for dropdown
     * 
     * @return array
     */
    private function getBanksForDropdown(): array
    {
        
        $banks = Bank::where('country', 'CZ')
            ->orderBy('created_at', 'desc')
            ->get()->toArray();

        foreach ($banks as $key => $bank) {
            $banks[$key]['text'] = $bank['name'] . ' (' . $bank['code'] . ')';
            $banks[$key]['value'] = $bank['code'];
        }
        $banks[0] = __('suppliers.fields.select_bank');

        return $banks;
    }

    /**
     * Get list of banks with codes for dropdown
     * 
     * @return array
     */
    private function getBanksForJs(): array
    {
        
        $banks = Bank::where('country', 'CZ')
            ->orderBy('created_at', 'desc')
            ->get()->toArray();

        $banksData = [];
        foreach ($banks as $key => $bank) {
            $banksData[$bank['code']]['text'] = $bank['name'] . ' (' . $bank['code'] . ')';
            $banksData[$bank['code']]['swift'] = $bank['swift']; 
        }

        return $banksData;
    }
}
