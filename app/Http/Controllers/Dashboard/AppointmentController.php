<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Customers\Models\Client;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Scheduling\Services\AppointmentService;
use App\Domain\Services\Models\Service;
use App\Http\Controllers\Controller;
use App\Models\Professional;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointmentService
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::current();
        $date = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))
            : \Carbon\Carbon::today();

        $professionalId = $request->query('professional');

        $appointments = $this->appointmentService->getByDate($tenantId, $date);
        $professionals = Professional::all()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
        ]);
        $clients = Client::all()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ]);
        $services = Service::all()->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'price' => $s->price,
            'duration_minutes' => $s->duration_minutes,
        ]);

        return Inertia::render('Dashboard/Calendar/Index', [
            'appointments' => $appointments,
            'selectedDate' => $date->toDateString(),
            'professionals' => $professionals,
            'clients' => $clients,
            'services' => $services,
            'selectedProfessionalId' => $professionalId ? (int) $professionalId : null,
        ]);
    }

    public function show(Appointment $appointment): Response
    {
        return Inertia::render('Dashboard/Calendar/Show', [
            'appointment' => $appointment->load(['client', 'professional', 'service']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'professional_id' => 'required|integer|exists:professionals,id',
                'client_id' => 'required|integer|exists:clients,id',
                'service_id' => 'required|integer|exists:services,id',
                'package_id' => 'nullable|integer|exists:packages,id',
                'start_at' => 'required|date',
                'end_at' => 'nullable|date',
                'status' => 'nullable|string',
                'price' => 'nullable|integer|min:0',
            ]);

            $validated['tenant_id'] = TenantContext::current();

            $this->appointmentService->create($validated);

            return redirect()->route('dashboard.calendar.index')
                ->with('success', 'Appointment created successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'professional_id' => 'nullable|integer|exists:professionals,id',
                'client_id' => 'nullable|integer|exists:clients,id',
                'service_id' => 'nullable|integer|exists:services,id',
                'package_id' => 'nullable|integer|exists:packages,id',
                'start_at' => 'nullable|date',
                'end_at' => 'nullable|date',
                'status' => 'nullable|string',
                'price' => 'nullable|integer|min:0',
            ]);

            $this->appointmentService->update($appointment, $validated);

            return redirect()->route('dashboard.calendar.index')
                ->with('success', 'Appointment updated successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $this->appointmentService->delete($appointment);

        return redirect()->route('dashboard.calendar.index')
            ->with('success', 'Appointment deleted successfully.');
    }
}