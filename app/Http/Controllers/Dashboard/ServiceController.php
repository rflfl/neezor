<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Domain\Services\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        $services = Service::all();

        return Inertia::render('Dashboard/Services/Index', [
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        Service::create(array_merge($validated, [
            'tenant_id' => $request->user()->tenant_id,
        ]));

        return redirect()->route('dashboard.services.index')
            ->with('success', 'Service created successfully.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $service->update($validated);

        return redirect()->route('dashboard.services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('dashboard.services.index')
            ->with('success', 'Service deleted successfully.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'services' => 'required|array',
            'services.*.name' => 'required|string|max:255',
            'services.*.duration_minutes' => 'required|integer|min:1',
            'services.*.price' => 'required|integer|min:0',
        ]);

        $tenantId = $request->user()->tenant_id;

        foreach ($validated['services'] as $data) {
            Service::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'duration_minutes' => $data['duration_minutes'],
                'price' => $data['price'],
                'is_active' => true,
            ]);
        }

        return redirect()->back();
    }
}