<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Customers\Models\Client;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Packages\Models\ClientPackage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(): Response
    {
        $clients = Client::where('is_active', true)->get();
        $inactiveClients = Client::where('is_active', false)->get();

        return Inertia::render('Dashboard/Clients/Index', [
            'clients' => $clients,
            'inactiveClients' => $inactiveClients,
        ]);
    }

    public function show(Client $client): Response
    {
        $client->load('appointments.service', 'appointments.professional');
        $packages = ClientPackage::where('client_id', $client->id)
            ->with('package.services')
            ->get();

        return Inertia::render('Dashboard/Clients/Show', [
            'client' => $client,
            'appointments' => $client->appointments->map(fn($a) => [
                'id' => $a->id,
                'start_at' => $a->start_at,
                'price' => $a->price,
                'status' => $a->status,
                'service' => $a->service ? [
                    'id' => $a->service->id,
                    'name' => $a->service->name,
                ] : null,
                'professional' => $a->professional ? [
                    'id' => $a->professional->id,
                    'name' => $a->professional->name,
                ] : null,
            ]),
            'packages' => $packages->map(fn($p) => [
                'id' => $p->id,
                'is_active' => $p->is_active,
                'used_sessions' => $p->used_sessions,
                'total_sessions' => $p->total_sessions,
                'expires_at' => $p->expires_at,
                'package' => $p->package ? [
                    'id' => $p->package->id,
                    'name' => $p->package->name,
                ] : null,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        Client::create(array_merge($validated, [
            'tenant_id' => $request->user()->tenant_id,
        ]));

        return redirect()->route('dashboard.clients.index')
            ->with('success', 'Client created successfully.');
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $client->update($validated);

        return redirect()->route('dashboard.clients.index')
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('dashboard.clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}