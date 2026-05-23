<?php

use App\Http\Controllers\Dashboard\ClientController;
use App\Http\Controllers\Dashboard\ProfessionalController;
use App\Http\Controllers\Dashboard\ServiceController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'tenant',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/dashboard/professionals', [ProfessionalController::class, 'index'])
        ->name('dashboard.professionals.index');
    Route::get('/dashboard/professionals/{professional}', [ProfessionalController::class, 'show'])
        ->name('dashboard.professionals.show');
    Route::post('/dashboard/professionals', [ProfessionalController::class, 'store'])
        ->name('dashboard.professionals.store');
    Route::put('/dashboard/professionals/{professional}', [ProfessionalController::class, 'update'])
        ->name('dashboard.professionals.update');
    Route::delete('/dashboard/professionals/{professional}', [ProfessionalController::class, 'destroy'])
        ->name('dashboard.professionals.destroy');

    Route::get('/dashboard/services', [ServiceController::class, 'index'])
        ->name('dashboard.services.index');
    Route::post('/dashboard/services', [ServiceController::class, 'store'])
        ->name('dashboard.services.store');
    Route::put('/dashboard/services/{service}', [ServiceController::class, 'update'])
        ->name('dashboard.services.update');
    Route::delete('/dashboard/services/{service}', [ServiceController::class, 'destroy'])
        ->name('dashboard.services.destroy');

    Route::get('/dashboard/clients', [ClientController::class, 'index'])
        ->name('dashboard.clients.index');
    Route::post('/dashboard/clients', [ClientController::class, 'store'])
        ->name('dashboard.clients.store');
    Route::put('/dashboard/clients/{client}', [ClientController::class, 'update'])
        ->name('dashboard.clients.update');
    Route::delete('/dashboard/clients/{client}', [ClientController::class, 'destroy'])
        ->name('dashboard.clients.destroy');
});
