<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Packages\Contracts\PackageServiceInterface;
use App\Domain\Packages\Models\Package;
use App\Domain\Services\Models\Service;
use App\Domain\Customers\Models\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function __construct(
        private readonly PackageServiceInterface $packageService
    ) {}

    public function index(): Response
    {
        $packages = Package::with('services')->get();
        $services = Service::where('is_active', true)->get()->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'price' => $s->price,
            'duration_minutes' => $s->duration_minutes,
        ]);

        return Inertia::render('Dashboard/Packages/Index', [
            'packages' => $packages,
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'valid_until_days' => 'required|integer|min:1',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.session_count' => 'required|integer|min:1',
        ]);

        $package = $this->packageService->createPackage([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'valid_until_days' => $validated['valid_until_days'],
        ]);

        foreach ($validated['services'] as $serviceData) {
            $this->packageService->addService($package, $serviceData['service_id'], $serviceData['session_count']);
        }

        return redirect()->route('dashboard.packages.index')
            ->with('success', 'Package created successfully.');
    }

    public function show(Package $package): Response
    {
        $package->load('services');
        $sessions = $this->packageService->getPackageSessions($package->id);

        return Inertia::render('Dashboard/Packages/Show', [
            'package' => $package,
            'sessions' => $sessions,
        ]);
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'valid_until_days' => 'required|integer|min:1',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.session_count' => 'required|integer|min:1',
        ]);

        $package = $this->packageService->updatePackage($package, [
            'name' => $validated['name'],
            'price' => $validated['price'],
            'valid_until_days' => $validated['valid_until_days'],
        ]);

        $package->services()->detach();
        foreach ($validated['services'] as $serviceData) {
            $this->packageService->addService($package, $serviceData['service_id'], $serviceData['session_count']);
        }

        return redirect()->route('dashboard.packages.index')
            ->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $this->packageService->deletePackage($package);

        return redirect()->route('dashboard.packages.index')
            ->with('success', 'Package deleted successfully.');
    }

    public function sessions(Package $package): Response
    {
        $package->load('services');
        $sessions = $this->packageService->getPackageSessions($package->id);
        $clients = Client::where('is_active', true)->get()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ]);

        return Inertia::render('Dashboard/Packages/Sessions', [
            'package' => $package,
            'sessions' => $sessions,
            'clients' => $clients,
        ]);
    }

    public function purchase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'client_id' => 'required|exists:clients,id',
        ]);

        $package = Package::find($validated['package_id']);
        $this->packageService->purchase(
            $request->user()->tenant_id,
            $validated['client_id'],
            $package
        );

        return redirect()->back()
            ->with('success', 'Package purchased successfully for client.');
    }
}