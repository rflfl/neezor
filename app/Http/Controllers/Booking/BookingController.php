<?php

namespace App\Http\Controllers\Booking;

use App\Domain\Customers\Models\Client;
use App\Domain\Scheduling\Models\Appointment;
use App\Domain\Scheduling\Services\AppointmentService;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Services\Models\Service;
use App\Http\Controllers\Controller;
use App\Models\Professional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly AppointmentService $appointmentService
    ) {}

    public function index(string $slug): Response
    {
        $tenant = $this->getTenantBySlug($slug);

        return Inertia::render('Booking/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
        ]);
    }

    public function services(string $slug): JsonResponse
    {
        $tenant = $this->getTenantBySlug($slug);
        $tenantId = $tenant->id;

        $services = Service::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'duration_minutes' => $s->duration_minutes,
                'price' => $s->price,
            ]);

        return response()->json([
            'services' => $services,
        ]);
    }

    public function professional(string $slug, Request $request): Response
    {
        $tenant = $this->getTenantBySlug($slug);
        $serviceId = $request->query('service_id');

        $service = null;
        if ($serviceId) {
            $service = Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('tenant_id', $tenant->id)
                ->first();
        }

        if (!$service) {
            return Inertia::location(route('booking.index', $tenant->slug));
        }

        return Inertia::render('Booking/Professional', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
            ],
        ]);
    }

    public function professionals(string $slug, Request $request): JsonResponse
    {
        $tenant = $this->getTenantBySlug($slug);
        $tenantId = $tenant->id;
        $serviceId = $request->query('service_id');

        if (!$serviceId) {
            return response()->json(['professionals' => []]);
        }

        $service = Service::withoutGlobalScopes()
            ->where('id', $serviceId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$service) {
            return response()->json(['professionals' => []]);
        }

        $professionals = Professional::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ]);

        return response()->json([
            'professionals' => $professionals,
        ]);
    }

    public function slots(string $slug, Request $request): JsonResponse
    {
        $tenant = $this->getTenantBySlug($slug);
        $tenantId = $tenant->id;

        $professionalId = $request->query('professional_id');
        $serviceId = $request->query('service_id');
        $dateStr = $request->query('date');

        if (!$serviceId || !$dateStr) {
            return response()->json(['slots' => []]);
        }

        if ($professionalId === null || $professionalId === '') {
            $professionalIds = Professional::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        } else {
            $professionalIds = [(int) $professionalId];
        }

        if (empty($professionalIds)) {
            return response()->json(['slots' => []]);
        }

        $date = Carbon::parse($dateStr);
        $allSlots = [];

        foreach ($professionalIds as $profId) {
            $slots = $this->availabilityService->getAvailableSlots(
                $tenantId,
                $profId,
                (int) $serviceId,
                $date
            );

            foreach ($slots as $slot) {
                $allSlots[] = [
                    'start' => $slot['start']->toIso8601String(),
                    'end' => $slot['end']->toIso8601String(),
                    'professional_id' => $profId,
                ];
            }
        }

        usort($allSlots, fn($a, $b) => $a['start'] <=> $b['start']);

        return response()->json([
            'slots' => array_values($allSlots),
        ]);
    }

    public function store(string $slug, Request $request): JsonResponse
    {
        $tenant = $this->getTenantBySlug($slug);
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'professional_id' => 'required|integer',
            'service_id' => 'required|integer',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
        ]);

        $client = Client::withoutGlobalScopes()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'phone' => $validated['client_phone'],
            ],
            [
                'name' => $validated['client_name'],
            ]
        );

        if ($client->wasRecentlyCreated || $client->name !== $validated['client_name']) {
            $client->update(['name' => $validated['client_name']]);
        }

        $service = Service::withoutGlobalScopes()->find($validated['service_id']);

        try {
            $appointment = $this->appointmentService->create([
                'tenant_id' => $tenantId,
                'professional_id' => $validated['professional_id'],
                'client_id' => $client->id,
                'service_id' => $validated['service_id'],
                'start_at' => Carbon::parse($validated['start_at']),
                'end_at' => Carbon::parse($validated['end_at']),
                'price' => $service->price,
                'status' => Appointment::STATUS_SCHEDULED,
            ]);

            return response()->json([
                'success' => true,
                'appointment' => [
                    'id' => $appointment->id,
                    'start_at' => $appointment->start_at->toIso8601String(),
                    'end_at' => $appointment->end_at->toIso8601String(),
                    'service' => [
                        'name' => $service->name,
                    ],
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 409);
        }
    }

    public function confirm(string $slug, Request $request): Response
    {
        $tenant = $this->getTenantBySlug($slug);
        $serviceId = $request->query('service_id');
        $professionalId = $request->query('professional_id');
        $startAt = $request->query('start_at');
        $endAt = $request->query('end_at');

        $service = null;
        if ($serviceId) {
            $service = Service::withoutGlobalScopes()
                ->where('id', $serviceId)
                ->where('tenant_id', $tenant->id)
                ->first();
        }

        $professional = null;
        if ($professionalId) {
            $professional = Professional::withoutGlobalScopes()
                ->where('id', $professionalId)
                ->where('tenant_id', $tenant->id)
                ->first();
        }

        if (!$service || !$startAt || !$endAt) {
            return Inertia::location(route('booking.index', $tenant->slug));
        }

        return Inertia::render('Booking/Confirm', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
            ],
            'professional' => $professional ? [
                'id' => $professional->id,
                'name' => $professional->name,
            ] : null,
            'slot' => [
                'start_at' => $startAt,
                'end_at' => $endAt,
            ],
        ]);
    }

    public function success(string $slug, Request $request): Response
    {
        $tenant = $this->getTenantBySlug($slug);
        $serviceName = $request->query('service_name', 'Servico');
        $startAt = $request->query('start_at');

        return Inertia::render('Booking/Success', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'appointment' => [
                'service' => ['name' => $serviceName],
                'start_at' => $startAt,
            ],
        ]);
    }

    public function generateToken(string $slug): JsonResponse
    {
        $tenant = $this->getTenantBySlug($slug);
        $bookingToken = \App\Models\BookingToken::generateForTenant($tenant->id);

        return response()->json([
            'token' => $bookingToken->token,
            'expires_at' => $bookingToken->expires_at->toIso8601String(),
            'url' => url('/booking/' . $tenant->slug . '?token=' . $bookingToken->token),
        ]);
    }

    private function getTenantBySlug(string $slug): \App\Models\Tenant
    {
        $tenant = \App\Models\Tenant::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        if (!$tenant) {
            abort(404, 'Salon not found');
        }

        return $tenant;
    }
}