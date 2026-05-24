<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Professional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfessionalController extends Controller
{
    public function index(): Response
    {
        $professionals = Professional::all();

        return Inertia::render('Dashboard/Professionals/Index', [
            'professionals' => $professionals,
        ]);
    }

    public function show(Professional $professional): Response
    {
        return Inertia::render('Dashboard/Professionals/Show', [
            'professional' => $professional,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        Professional::create($validated);

        return redirect()->route('dashboard.professionals.index')
            ->with('success', 'Professional created successfully.');
    }

    public function update(Request $request, Professional $professional): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $professional->update($validated);

        return redirect()->route('dashboard.professionals.index')
            ->with('success', 'Professional updated successfully.');
    }

    public function destroy(Professional $professional): RedirectResponse
    {
        $professional->delete();

        return redirect()->route('dashboard.professionals.index')
            ->with('success', 'Professional deleted successfully.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'professionals' => 'required|array',
            'professionals.*.name' => 'required|string|max:255',
            'professionals.*.email' => 'nullable|email|max:255',
            'professionals.*.phone' => 'nullable|string|max:20',
            'professionals.*.commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $tenantId = $request->user()->tenant_id;

        foreach ($validated['professionals'] as $data) {
            Professional::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'commission_rate' => $data['commission_rate'] ?? 40,
                'is_active' => true,
            ]);
        }

        return redirect()->back();
    }
}