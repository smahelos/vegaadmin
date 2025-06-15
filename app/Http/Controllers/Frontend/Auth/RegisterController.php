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
use App\Services\BankService;
use App\Services\CountryService;
use App\Services\LocaleService;
use App\Repositories\SupplierRepository;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    use UserFormFields;

    /**
     * Bank service instance
     * 
     * @var BankService
     */
    protected $bankService;

    /**
     * Country service instance
     * 
     * @var CountryService
     */
    protected $countryService;

    /**
     * Locale service instance
     * 
     * @var LocaleService
     */
    protected $localeService;

    /**
     * Supplier repository instance
     * 
     * @var SupplierRepository
     */
    protected $supplierRepository;

    /**
     * Constructor
     * 
     * @param BankService $bankService
     * @param CountryService $countryService
     * @param LocaleService $localeService
     * @param SupplierRepository $supplierRepository
     */
    public function __construct(
        BankService $bankService,
        CountryService $countryService,
        LocaleService $localeService,
        SupplierRepository $supplierRepository
    ) {
        $this->bankService = $bankService;
        $this->countryService = $countryService;
        $this->localeService = $localeService;
        $this->supplierRepository = $supplierRepository;
    }

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
        $banks = $this->bankService->getBanksForDropdown();

        // Get banksData for JD bank-fields.js
        $banksData = $this->bankService->getBanksForJs();

        // Get countries for dropdown
        $countries = $this->countryService->getCountryCodesForSelect();

        return view('auth.register', [
            'userFields' => $userFields,
            'passwordFields' => $passwordFields,
            'banks' => $banks,
            'banksData' => $banksData,
            'countries' => $countries
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
            // Create new user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // Assign frontend_user role to new user
            $frontendRole = Role::firstOrCreate(['name' => 'frontend_user']);
            $user->assignRole($frontendRole);

            // Create default supplier for new user using repository
            $supplierData = [
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
            ];
                
            $supplier = $this->supplierRepository->create($supplierData);
        
            // Fire registered event
            event(new Registered($user));
        
            // Login user automatically
            Auth::login($user);
        
            // Set locale and redirect
            $locale = $this->localeService->determineLocale($request->get('lang'));
            
            return redirect()->route('home', ['lang' => $locale])->with('success', __('users.auth.registration_success'));
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage(), [
                'email' => $validatedData['email'] ?? 'unknown',
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If error occurs, try to delete partially created user to avoid data inconsistency
            if (isset($user) && $user->exists) {
                $user->delete();
            }
            
            return back()->withInput()->with('error', __('users.auth.registration_failed'));
        }
    }
}
