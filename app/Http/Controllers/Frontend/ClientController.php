<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Http\Requests\ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Traits\ClientFormFields;
use App\Services\LocaleService;
use App\Services\CountryService;
use App\Repositories\ClientRepository;

class ClientController extends Controller
{
    use ClientFormFields;

    /**
     * @var ClientRepository
     */
    protected $clientRepository;

    /**
     * @var CountryService
     */
    protected $countryService;

    /**
     * @var LocaleService
     */
    protected $localeService;

    /**
     * Constructor
     * 
     * @param ClientRepository $clientRepository
     * @param CountryService $countryService
     * @param LocaleService $localeService
     */
    public function __construct(
        ClientRepository $clientRepository,
        CountryService $countryService,
        LocaleService $localeService
    ) {
        $this->clientRepository = $clientRepository;
        $this->countryService = $countryService;
        $this->localeService = $localeService;
    }

    /**
     * Display paginated list of user clients
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {       
        return view('frontend.clients.index');
    }

    /**
     * Show form for creating a new client
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $fields = $this->getClientFields();

        $user = Auth::user();
        $userInfo = [
            'name' => $user->name ?? '',
            'street' => $user->street ?? '',
            'city' => $user->city ?? '',
            'zip' => $user->zip ?? '',
            'country' => $user->country ?? 'CZ',
            'ico' => $user->ico ?? '',
            'dic' => $user->dic ?? '',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'description' => $user->description ?? '',
        ];
        
        // Get countries for dropdown
        $countries = $this->countryService->getCountryCodesForSelect();
        
        return view('frontend.clients.create', [
            'fields' => $fields,
            'userInfo' => $userInfo,
            'countries' => $countries,
        ]);
    }
    
    /**
     * Store a new client
     *
     * @param ClientRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ClientRequest $request)
    {
        try {
            $validatedData = $request->validated();
            
            // Use repository to create the client
            $client = $this->clientRepository->create($validatedData);
            
            // Get locale from route parameters (since we're in localized route group)
            $locale = $request->route('locale') ?? 'cs';
            
            return redirect()->route('frontend.clients', ['locale' => $locale])
                            ->with('success', __('clients.messages.created'));
        } catch (\Exception $e) {
            Log::error('Error creating client: ' . $e->getMessage());
            
            return back()->withInput()
                        ->with('error', __('clients.messages.error_create'));
        }
    }
    
    /**
     * Display client details and related invoices
     *
     * @param string $locale
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $locale, int $id)
    {
        try {
            // Ignore requests for static files
            if (preg_match('/\.(js\.map|css\.map|js|css|png|jpg|gif|svg|woff|woff2|ttf|eot)$/', $id)) {
                return response()->json(['error' => 'Not found'], 404);
            }
            
            // Get client using repository
            $client = Client::where('user_id', Auth::id())->findOrFail($id);
            
            // Get related invoices with eager loading
            $invoices = $client->invoices()
                ->with(['paymentMethod', 'paymentStatus'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return view('frontend.clients.show', compact('client', 'invoices'));
        } catch (ModelNotFoundException $e) {
            Log::warning('Trying to view nonexistent client with ID: ' . $id);
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_show'));
        } catch (\Exception $e) {
            Log::error('Error viewing client: ' . $e->getMessage());
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_show'));
        }
    }
    
    /**
     * Show form for editing a client
     *
     * @param string $locale
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(string $locale, int $id)
    {
        try {
            // Get client using repository
            $client = Client::where('user_id', Auth::id())
                            ->findOrFail($id);
                            
            $fields = $this->getClientFields();
            
            // Get countries for dropdown
            $countries = $this->countryService->getCountryCodesForSelect();
            
            return view('frontend.clients.edit', [
                'client' => $client,
                'fields' => $fields,
                'countries' => $countries
            ]);
            
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found for edit: ' . $e->getMessage());
            
            return redirect()->route('frontend.clients', ['locale' => $locale])
                             ->with('error', __('clients.messages.error_update'));
        } catch (\Exception $e) {
            Log::error('Error editing client: ' . $e->getMessage());
            
            return redirect()->route('frontend.clients', ['locale' => $locale])
                             ->with('error', __('clients.messages.error_edit'));
        }
    }
    
    /**
     * Update client data
     *
     * @param ClientRequest $request
     * @param string $locale
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ClientRequest $request, string $locale, int $id)
    {
        try {
            $client = Client::where('user_id', Auth::id())->findOrFail($id);
            
            $validatedData = $request->validated();
            $validatedData['is_default'] = isset($validatedData['is_default']) && $validatedData['is_default'] == 1;

            // Update client
            $client->update($validatedData);
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('success', __('clients.messages.updated'));
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found during update #' . $id . ': ' . $e->getMessage());
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_update'));
        } catch (\Exception $e) {
            Log::error('Error updating client #' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_update'));
        }
    }
    
    /**
     * Delete client if it has no associated invoices
     *
     * @param string $locale
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $locale, int $id)
    {
        try {
            $client = Client::where('user_id', Auth::id())->findOrFail($id);
            
            // Check if client has invoices
            if ($client->invoices->count() > 0) {
                return redirect()
                    ->route('frontend.clients', ['locale' => $locale])
                    ->with('error', __('clients.messages.error_delete_invoices'));
            }
            
            // Delete client
            $client->delete();
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('success', __('clients.messages.deleted'));
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found for delete #' . $id . ': ' . $e->getMessage());
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_delete'));
        } catch (\Exception $e) {
            Log::error('Error deleting client #' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_delete'));
        }
    }
    
    /**
     * Set client as default
     *
     * @param string $locale
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setDefault(string $locale, int $id)
    {
        try {
            // Find client
            $client = Client::where('user_id', Auth::id())->findOrFail($id);
            
            // Remove default flag from all other clients
            Client::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
            
            // Set this client as default
            $client->update(['is_default' => true]);
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('success', __('clients.messages.set_default'));
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found for setting default #' . $id . ': ' . $e->getMessage());
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_set_default'));
        } catch (\Exception $e) {
            Log::error('Error setting client as default #' . $id . ': ' . $e->getMessage());
            
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_set_default'));
        }
    }
}
