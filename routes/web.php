<?php

use App\Http\Controllers\Agent;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PickupRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TruckCalendarController;
use App\Http\Controllers\TruckComplaintController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect(auth()->user()->homeUrl()) : view('welcome'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

    // Citoyens et agents de collecte : signaler, suivre ses signalements, demander une collecte.
    Route::middleware('role:citoyen,agent')->group(function () {
        Route::get('/carte', [MapController::class, 'index'])->name('map');

        Route::get('/signaler', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/signaler', [ReportController::class, 'store'])->name('reports.store');
        Route::get('/mes-signalements', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/demandes', [PickupRequestController::class, 'index'])->name('pickups.index');
        Route::get('/demandes/nouvelle', [PickupRequestController::class, 'create'])->name('pickups.create');
        Route::post('/demandes', [PickupRequestController::class, 'store'])->name('pickups.store');
    });

    // Citoyens : calendrier du camion de leur village et signalement d'un problème de camion.
    Route::middleware('role:citoyen')->group(function () {
        Route::get('/camion', [TruckCalendarController::class, 'index'])->name('trucks.index');
        Route::post('/camion/{truck}/probleme', [TruckComplaintController::class, 'store'])->name('trucks.complaints.store');
    });

    // Agents de collecte : tournée du jour et planning.
    Route::middleware('role:agent')->prefix('agent')->name('agent.')->group(function () {
        Route::get('/tournee', [Agent\RouteController::class, 'index'])->name('route');
        Route::post('/tournee/points/{stop}', [Agent\RouteController::class, 'toggleStop'])->name('stops.toggle');
        Route::get('/planning', [Agent\PlanningController::class, 'index'])->name('planning');

        Route::post('/signalements/{report}/demarrer', [Agent\MissionController::class, 'startReport'])->name('reports.start');
        Route::post('/signalements/{report}/nettoye', [Agent\MissionController::class, 'finishReport'])->name('reports.finish');
        Route::post('/demandes/{pickup}/effectuee', [Agent\MissionController::class, 'finishPickup'])->name('pickups.finish');
    });
});
