<?php

use App\Domain\Cashbox\Controllers\CashboxController;
use App\Domain\Commission\Controllers\CommissionController;
use App\Domain\Expenses\Controllers\ExpenseController;
use App\Http\Controllers\Dashboard\AppointmentController;
use App\Http\Controllers\Dashboard\ClientController;
use App\Http\Controllers\Dashboard\PackageController;
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

    Route::get('/dashboard/calendar', [AppointmentController::class, 'index'])
        ->name('dashboard.calendar.index');
    Route::get('/dashboard/calendar/professional/{professional}', [AppointmentController::class, 'index'])
        ->name('dashboard.calendar.professional');
    Route::get('/dashboard/calendar/{appointment}', [AppointmentController::class, 'show'])
        ->name('dashboard.calendar.show');
    Route::post('/dashboard/calendar', [AppointmentController::class, 'store'])
        ->name('dashboard.calendar.store');
    Route::put('/dashboard/calendar/{appointment}', [AppointmentController::class, 'update'])
        ->name('dashboard.calendar.update');
    Route::delete('/dashboard/calendar/{appointment}', [AppointmentController::class, 'destroy'])
        ->name('dashboard.calendar.destroy');

    Route::get('/dashboard/packages', [PackageController::class, 'index'])
        ->name('dashboard.packages.index');
    Route::post('/dashboard/packages', [PackageController::class, 'store'])
        ->name('dashboard.packages.store');
    Route::get('/dashboard/packages/{package}', [PackageController::class, 'show'])
        ->name('dashboard.packages.show');
    Route::put('/dashboard/packages/{package}', [PackageController::class, 'update'])
        ->name('dashboard.packages.update');
    Route::delete('/dashboard/packages/{package}', [PackageController::class, 'destroy'])
        ->name('dashboard.packages.destroy');
    Route::get('/dashboard/packages/{package}/sessions', [PackageController::class, 'sessions'])
        ->name('dashboard.packages.sessions');
    Route::post('/dashboard/packages/purchase', [PackageController::class, 'purchase'])
        ->name('dashboard.packages.purchase');

    Route::get('/dashboard/cashbox', [CashboxController::class, 'index'])
        ->name('dashboard.cashbox.index');
    Route::post('/dashboard/cashbox', [CashboxController::class, 'store'])
        ->name('dashboard.cashbox.store');
    Route::post('/dashboard/cashbox/entry', [CashboxController::class, 'entry'])
        ->name('dashboard.cashbox.entry');
    Route::post('/dashboard/cashbox/expense', [CashboxController::class, 'expense'])
        ->name('dashboard.cashbox.expense');
    Route::post('/dashboard/cashbox/close', [CashboxController::class, 'close'])
        ->name('dashboard.cashbox.close');
    Route::get('/dashboard/cashbox/categories', [CashboxController::class, 'categories'])
        ->name('dashboard.cashbox.categories');

    Route::get('/dashboard/commissions', [CommissionController::class, 'index'])
        ->name('dashboard.commissions.index');
    Route::get('/dashboard/commissions/professional/{professional}', [CommissionController::class, 'byProfessional'])
        ->name('dashboard.commissions.professional');
    Route::post('/dashboard/commissions/pay', [CommissionController::class, 'pay'])
        ->name('dashboard.commissions.pay');
    Route::post('/dashboard/commissions/adjust', [CommissionController::class, 'adjust'])
        ->name('dashboard.commissions.adjust');

    Route::get('/dashboard/expenses', [ExpenseController::class, 'index'])
        ->name('dashboard.expenses.index');
    Route::post('/dashboard/expenses', [ExpenseController::class, 'store'])
        ->name('dashboard.expenses.store');
    Route::get('/dashboard/expenses/{expense}', [ExpenseController::class, 'show'])
        ->name('dashboard.expenses.show');
    Route::put('/dashboard/expenses/{expense}', [ExpenseController::class, 'update'])
        ->name('dashboard.expenses.update');
    Route::delete('/dashboard/expenses/{expense}', [ExpenseController::class, 'destroy'])
        ->name('dashboard.expenses.destroy');
    Route::get('/dashboard/dre', [ExpenseController::class, 'dre'])
        ->name('dashboard.dre');
});
