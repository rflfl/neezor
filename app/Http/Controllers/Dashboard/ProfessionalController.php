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
}