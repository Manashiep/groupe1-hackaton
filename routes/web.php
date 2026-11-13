<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Agent;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Citizen;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pages publiques
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/calendrier', [HomeController::class, 'calendar'])->name('calendar');
Route::get('/carte', [HomeController::class, 'map'])->name('map');
Route::get('/quartiers/{quartier}', [HomeController::class, 'quartier'])->name('quartiers.show');

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/mon-espace', fn () => redirect(auth()->user()->homeUrl()))->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/tout-lire', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');

    /*
    |----------------------------------------------------------------------
    | Espace citoyen
    |----------------------------------------------------------------------
    */
    Route::middleware('role:citoyen')->prefix('espace')->name('citizen.')->group(function () {
        Route::get('/', [Citizen\DashboardController::class, 'index'])->name('dashboard');
        Route::patch('/quartier', [Citizen\DashboardController::class, 'updateQuartier'])->name('quartier.update');

        Route::get('/signalements', [Citizen\ReportController::class, 'index'])->name('reports.index');
        Route::get('/signalements/nouveau', [Citizen\ReportController::class, 'create'])->name('reports.create');
        Route::post('/signalements', [Citizen\ReportController::class, 'store'])->name('reports.store');
        Route::get('/signalements/{report}', [Citizen\ReportController::class, 'show'])->name('reports.show');

        Route::post('/collectes/{collection}/non-effectuee', [Citizen\CollectionComplaintController::class, 'store'])->name('collections.complain');

        Route::get('/demandes', [Citizen\PickupRequestController::class, 'index'])->name('pickups.index');
        Route::get('/demandes/nouvelle', [Citizen\PickupRequestController::class, 'create'])->name('pickups.create');
        Route::post('/demandes', [Citizen\PickupRequestController::class, 'store'])->name('pickups.store');
    });

    /*
    |----------------------------------------------------------------------
    | Espace agent de collecte
    |----------------------------------------------------------------------
    */
    Route::middleware('role:agent')->prefix('agent')->name('agent.')->group(function () {
        Route::get('/', [Agent\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/planning', [Agent\DashboardController::class, 'planning'])->name('planning');

        Route::post('/collectes/{collection}/demarrer', [Agent\MissionController::class, 'startCollection'])->name('collections.start');
        Route::post('/collectes/{collection}/terminer', [Agent\MissionController::class, 'completeCollection'])->name('collections.complete');
        Route::post('/collectes/{collection}/probleme', [Agent\MissionController::class, 'failCollection'])->name('collections.fail');
        Route::post('/interventions/{intervention}/demarrer', [Agent\MissionController::class, 'startIntervention'])->name('interventions.start');
        Route::post('/interventions/{intervention}/terminer', [Agent\MissionController::class, 'completeIntervention'])->name('interventions.complete');
    });

    /*
    |----------------------------------------------------------------------
    | Espace de gestion (responsables municipaux et administrateurs)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:responsable,admin')->prefix('gestion')->name('admin.')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/signalements', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/signalements/{report}', [Admin\ReportController::class, 'show'])->name('reports.show');
        Route::patch('/signalements/{report}/statut', [Admin\ReportController::class, 'updateStatus'])->name('reports.status');
        Route::post('/signalements/{report}/intervention', [Admin\InterventionController::class, 'storeForReport'])->name('reports.intervention');

        Route::get('/interventions', [Admin\InterventionController::class, 'index'])->name('interventions.index');
        Route::patch('/interventions/{intervention}', [Admin\InterventionController::class, 'update'])->name('interventions.update');

        Route::get('/collectes', [Admin\CollectionController::class, 'index'])->name('collections.index');
        Route::post('/collectes/generer', [Admin\CollectionController::class, 'generate'])->name('collections.generate');
        Route::post('/collectes/rappels', [Admin\CollectionController::class, 'sendReminders'])->name('collections.reminders');
        Route::patch('/collectes/{collection}', [Admin\CollectionController::class, 'update'])->name('collections.update');
        Route::post('/collectes/{collection}/rattrapage', [Admin\CollectionController::class, 'reschedule'])->name('collections.reschedule');

        Route::get('/itineraires', [Admin\RouteController::class, 'index'])->name('routes.index');

        Route::get('/calendrier', [Admin\ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/calendrier', [Admin\ScheduleController::class, 'store'])->name('schedules.store');
        Route::patch('/calendrier/{schedule}', [Admin\ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/calendrier/{schedule}', [Admin\ScheduleController::class, 'destroy'])->name('schedules.destroy');

        Route::get('/demandes', [Admin\PickupRequestController::class, 'index'])->name('pickups.index');
        Route::post('/demandes/{pickup}/accepter', [Admin\PickupRequestController::class, 'accept'])->name('pickups.accept');
        Route::post('/demandes/{pickup}/refuser', [Admin\PickupRequestController::class, 'refuse'])->name('pickups.refuse');

        Route::resource('quartiers', Admin\QuartierController::class)->except('show');
        Route::resource('equipes', Admin\TeamController::class)->except('show')
            ->parameters(['equipes' => 'team'])->names('teams');

        Route::middleware('role:admin')->group(function () {
            Route::resource('utilisateurs', Admin\UserController::class)->except(['show', 'destroy'])
                ->parameters(['utilisateurs' => 'user'])->names('users');
        });
    });
});
