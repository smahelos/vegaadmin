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

class ClientController extends Controller
{
    use ClientFormFields;

    /**
     * Display paginated list of user clients
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $clients = Client::where('user_id', Auth::id())
            ->orderBy('name')
            ->paginate(10);
            
        return view('frontend.clients.index', compact('clients'));
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
            'country' => $user->country ?? 'Czech Republic',
            'ico' => $user->ico ?? '',
            'dic' => $user->dic ?? '',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'description' => $user->description ?? '',
        ];
        
        return view('frontend.clients.create', [
            'fields' => $fields,
            'userInfo' => $userInfo,
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
            
            if (Client::where('user_id', Auth::id())->count() === 0) {
                $validatedData['is_default'] = true;
            }
            
            $validatedData['user_id'] = Auth::id();
            
            $client = Client::create($validatedData);
            
            return redirect()->route('frontend.clients', ['lang' => app()->getLocale()])
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
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        try {
            // Ignore requests for static files
            if (preg_match('/\.(js\.map|css\.map|js|css|png|jpg|gif|svg|woff|woff2|ttf|eot)$/', $id)) {
                return response()->json(['error' => 'Not found'], 404);
            }

            // Check if ID is numeric
            if (!is_numeric($id)) {
                Log::warning('Wrong client ID: ' . $id);
                return redirect()
                    ->route('frontend.clients', ['lang' => app()->getLocale()])
                    ->with('error', __('clients.messages.invalid_id'));
            }
            
            $client = Client::where('user_id', Auth::id())->findOrFail($id);
            $invoices = $client->invoices()
                ->with(['paymentMethod', 'paymentStatus']) // Eager load related models
                ->orderBy('created_at', 'desc')
                ->get();
            
            return view('frontend.clients.show', compact('client', 'invoices'));
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('frontend.clients', ['lang' => app()->getLocale()])
                ->with('error', __('clients.messages.error_update'));
        }
    }
    
    /**
     * Show form for editing a client
     *
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        try {
            $client = Client::where('user_id', Auth::id())
                            ->findOrFail($id);
                            
            $fields = $this->getClientFields();
            
            return view('frontend.clients.edit', [
                'client' => $client,
                'fields' => $fields
            ]);
            
        } catch (ModelNotFoundException $e) {
            return redirect()->route('frontend.clients', ['lang' => app()->getLocale()])
                             ->with('error', __('clients.messages.error_update'));
        }
    }
    
    /**
     * Update client data
     *
     * @param ClientRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ClientRequest $request, $id)
    {
        try {
            $client = Client::where('user_id', Auth::id())->findOrFail($id);
            
            $validatedData = $request->validated();
            $validatedData['is_default'] = isset($validatedData['is_default']) && $validatedData['is_default'] == 1;

            $client->update($validatedData);
            
            return redirect()
                ->route('frontend.clients', ['lang' => app()->getLocale()])
                ->with('success', __('clients.messages.updated'));
        } catch (ModelNotFoundException $e) {
            Log::error('Error updating client #' . $id . ': ' . $e->getMessage());
            return redirect()
                ->route('frontend.clients', ['lang' => app()->getLocale()])
                ->with('error', __('clients.messages.error_update'));
        }
    }
    
    /**
     * Delete client if it has no associated invoices
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $client = Client::where('user_id', Auth::id())->findOrFail($id);
            
            if ($client->invoices->count() > 0) {
                return redirect()
                    ->route('frontend.clients', ['lang' => app()->getLocale()])
                    ->with('error', __('clients.messages.error_delete_invoices'));
            }
            
            $client->delete();
            
            return redirect()
                ->route('frontend.clients', ['lang' => app()->getLocale()])
                ->with('success', __('clients.messages.deleted'));
        } catch (ModelNotFoundException $e) {
            Log::error('Error deleting client #' . $id . ': ' . $e->getMessage());
            return redirect()
                ->route('frontend.clients', ['lang' => app()->getLocale()])
                ->with('error', __('clients.messages.error_delete'));
        }
    }
}