<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\WelcomeController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\CivilianAccountController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\IncidentReportController;
use App\Http\Controllers\Web\MonitoringDashboardController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\SettingsController;
use App\Support\SystemVersion;
use Illuminate\Support\Facades\Route;

Route::get('/system/version', static function (SystemVersion $systemVersion) {
    return response()->json([
        'version' => $systemVersion->current(),
        'generated_at' => now()->toIso8601String(),
    ], 200, [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
})->name('system.version');

Route::middleware('guest')->group(function (): void {
    Route::get('/', WelcomeController::class)->name('welcome');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/monitoring', MonitoringDashboardController::class)->name('monitoring');
    Route::redirect('/home', '/dashboard');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    });

    Route::prefix('civilian-accounts')->name('civilian-accounts.')->group(function (): void {
        Route::get('/', [CivilianAccountController::class, 'index'])->name('index');
        Route::put('/{civilianAccount}', [CivilianAccountController::class, 'update'])->name('update');
    });

    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [IncidentReportController::class, 'index'])->name('index');
        Route::get('/create', [IncidentReportController::class, 'create'])->name('create');
        Route::post('/', [IncidentReportController::class, 'store'])->name('store');
        Route::post('/{incidentReport}/coordination', [IncidentReportController::class, 'updateCoordination'])->name('coordination');
        Route::delete('/{incidentReport}', [IncidentReportController::class, 'destroy'])->name('destroy');
        Route::get('/{incidentReport}/evidence', [IncidentReportController::class, 'evidence'])->name('evidence');
        Route::get('/{incidentReport}/selfie', [IncidentReportController::class, 'selfie'])->name('selfie');
        Route::get('/{incidentReport}', [IncidentReportController::class, 'show'])->name('show');
        Route::get('/{incidentReport}/success', [IncidentReportController::class, 'success'])->name('success');
        Route::get('/{incidentReport}/ai-severity', [IncidentReportController::class, 'severity'])->name('severity');
        Route::get('/{incidentReport}/transmissions', [IncidentReportController::class, 'transmissions'])->name('transmissions');
    });

    Route::get('/profile/photo', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/readiness', [SettingsController::class, 'readiness'])->name('settings.readiness');
    Route::post('/settings/preferences', [SettingsController::class, 'update'])->name('settings.update');
});
