<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function step(Request $request, int $step)
    {
        $step = max(1, min(3, $step));

        $data = ['currentStep' => $step];

        if ($step >= 2) {
            $data['professionals'] = Professional::all();
        }

        return Inertia::render("Onboarding/Step{$step}", $data);
    }

    public function complete(Request $request)
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.professional_id' => 'required|integer|exists:professionals,id',
            'schedules.*.working_hours' => 'required|array',
        ]);

        $user = $request->user();
        $tenant = $user->tenant;

        DB::transaction(function () use ($tenant) {
            $tenant->update(['has_completed_onboarding' => true]);
        });

        return redirect()->route('dashboard');
    }
}