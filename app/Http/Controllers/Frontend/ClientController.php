<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
// Removed direct Client model usage; delegate to party service
use App\Http\Requests\ClientRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException; // still used for catch blocks
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Forms\Party\ClientFormFields;
use App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface;
use App\Application\User\Contracts\UELSApplicationServiceInterface as UELSService;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;

class ClientController extends Controller
{
    use ClientFormFields; // Frontend limits via observers

    /**
    * @var PartyApplicationServiceInterface
    */
    protected $partyService;

    /**
     * @var CountryApplicationServiceInterface
     */
    protected $countryService;

    /**
     * @var UELSService
     */
    protected $limitService;

    /**
     * Constructor
     *
     * @param PartyApplicationServiceInterface $partyService
     * @param CountryApplicationServiceInterface $countryService
     * @param UELSService $limitService
     */
    public function __construct(
        PartyApplicationServiceInterface $partyService,
        CountryApplicationServiceInterface $countryService,
        UELSService $limitService
    ) {
        $this->partyService = $partyService;
        $this->countryService = $countryService;
        $this->limitService = $limitService;
    }

    /**
     * Display paginated list of user clients
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // get current logged user's client limits
        $limitsData = $this->getClientsLimitStats();

        return view('frontend.clients.index', compact('limitsData'));
    }

    /**
     * Show form for creating a new client
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $fields = $this->getClientFields();

        // Get current logged user
        $user = Auth::user();

        // get current logged user's client limits
        $limitsData = $this->getClientsLimitStats($user);

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
            'limitsData' => $limitsData
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

            // Get User
            $user = Auth::user();
            // Pre-limit check (generic)
            if ($user) {
                $canCreate = $this->limitService->canUserCreateEntity($user->id, 'client');
                if (!$canCreate) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', trans('clients.messages.limit_exceeded'));
                }
            }
            $resolved = $this->partyService->resolveOrCreateClientWithFlag($user->id, $validatedData);
            $client = $resolved['client'];

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
            if (preg_match('/\.(js\.map|css\.map|js|css|png|jpg|gif|svg|woff|woff2|ttf|eot)$/', (string)$id)) {
                return redirect()
                    ->route('frontend.clients', ['locale' => $locale])
                    ->with('error', __('clients.messages.not_found'));
            }

            // Get current logged user
            $user = Auth::user();

            // Get client using repository
            $client = $this->partyService->findClient($user->id, $id);

            // get current logged user's client limits
            $limitsData = $this->getClientsLimitStats($user);

            return view('frontend.clients.show', compact('client', 'limitsData'));
        } catch (ModelNotFoundException $e) {
            Log::warning('Trying to view nonexistent client with ID: ' . $id);

            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.not_found'));
        } catch (\Exception $e) {
            Log::error('Error viewing client: ' . $e->getMessage());

            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.not_found'));
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
            // Get current logged user
            $user = Auth::user();

            // Get client using repository
            $client = $this->partyService->findClient($user->id, $id);

            $fields = $this->getClientFields();

            // Get countries for dropdown
            $countries = $this->countryService->getCountryCodesForSelect();

            // get current logged user's client limits
            $limitsData = $this->getClientsLimitStats($user);

            return view('frontend.clients.edit', [
                'client' => $client,
                'fields' => $fields,
                'countries' => $countries,
                'limitsData' => $limitsData
            ]);

        } catch (ModelNotFoundException $e) {
            Log::error('Client not found for edit: ' . $e->getMessage());

            return redirect()->route('frontend.clients', ['locale' => $locale])
                             ->with('error', __('clients.messages.error_update'));
        } catch (\Exception $e) {
            Log::error('Error editing client: ' . $e->getMessage());
            return redirect()->route('frontend.clients', ['locale' => $locale])
                             ->with('error', __('clients.messages.error_update'));
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
            // Get current logged user
            $user = Auth::user();

            // Get client using party service
            $client = $this->partyService->findClient($user->id, $id);

            $validatedData = $request->validated();
            $validatedData['is_default'] = isset($validatedData['is_default']) && $validatedData['is_default'] == 1;

            // Update client
            $this->partyService->updateClient($client->id, $validatedData);

            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('success', __('clients.messages.updated'));
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found during update #' . $id . ': ' . $e->getMessage());

            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_update'));
        } catch (\Exception $e) {
            Log::error('Error updating client #' . $id . ': ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
            // Get current logged user
            $user = Auth::user();

            // Get client using repository
            $client = $this->partyService->findClient($user->id, $id);
            if (!$this->partyService->deleteClient($user->id, $client->id)) {
                return redirect()->route('frontend.clients', ['locale' => $locale])
                    ->with('error', __('clients.messages.error_delete_invoices'));
            }

            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('success', __('clients.messages.deleted'));
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found for delete #' . $id . ': ' . $e->getMessage());

            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_delete'));
        } catch (\Exception $e) {
            Log::error('Error deleting client #' . $id . ': ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
            // Get current logged user
            $user = Auth::user();

            // Get client using repository
            $client = $this->partyService->findClient($user->id, $id);
            $this->partyService->setClientDefault($user->id, $client->id);

            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('success', __('clients.messages.updated'));
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found for setting default #' . $id . ': ' . $e->getMessage());

            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_update'));
        } catch (\Exception $e) {
            Log::error('Error setting client as default #' . $id . ': ' . $e->getMessage());
            return redirect()
                ->route('frontend.clients', ['locale' => $locale])
                ->with('error', __('clients.messages.error_update'));
        }
    }

    /**
     * Get current user's products limits
     *
     * @param \App\Models\User|null $user
     * @return array
     */
    public function getClientsLimitStats($user = null): array
    {
        if ($user === null) {
            $user = Auth::user();
        }
        if ($user) {
            $bestPeriod = $this->limitService->getBestPeriodType($user->id, 'client', 'count');
            $stats = $this->limitService->getUsageStatistics($user->id, 'client', 'count', $bestPeriod);
            $limit = $stats['limit'] ?? (($stats['remaining'] ?? null) !== null ? (int)$stats['remaining'] + (int)($stats['current_usage'] ?? 0) : 0);
            $current = (int)($stats['current_usage'] ?? 0);
            $canCreate = $stats['can_create'] ?? ($limit > $current);
            return [
                'limit' => $limit,
                'current_usage' => $current,
                'allowed' => (bool)$canCreate,
            ];
        }
        else {
            return [
                'limit' => 0,
                'current_usage' => 0,
                'allowed' => false
            ];
        }
    }
}
