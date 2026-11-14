<?php

use App\Http\Controllers\Agent;
use App\Http\Controllers\AssociationController;
use App\Http\Controllers\AssociationManagementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PickupRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SimuPay;
use App\Http\Controllers\TruckCalendarController;
use App\Http\Controllers\TruckComplaintController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect(auth()->user()->homeUrl()) : view('welcome'))->name('home');

/*
| SimuPay : fausse passerelle de paiement, comme si c'était une autre entreprise.
| On la lance sur un second serveur (php artisan serve --port=8001) pour simuler un service extérieur.
*/
Route::prefix('simupay')->name('simupay.')->group(function () {
    Route::post('/api/payments', [SimuPay\ApiController::class, 'store'])->name('api.store');
    Route::get('/api/payments/{transaction}', [SimuPay\ApiController::class, 'show'])->name('api.show');
    Route::get('/checkout/{transaction}', [SimuPay\CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{transaction}', [SimuPay\CheckoutController::class, 'confirm'])->name('checkout.confirm');
});

// Webhook : SimuPay prévient CleanComores du résultat d'un paiement (adresse publique, message signé).
Route::post('/paiements/webhook', PaymentWebhookController::class)->name('payments.webhook');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

    // Citoyens et agents : carte, signalements, demandes de collecte, associations, profil.
    Route::middleware('role:citoyen,agent')->group(function () {
        Route::get('/carte', [MapController::class, 'index'])->name('map');
        Route::get('/moi', [ProfileController::class, 'index'])->name('profile');
        Route::patch('/moi/village', [ProfileController::class, 'updateHomeVillage'])->name('profile.home-village');

        Route::get('/signaler', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/signaler', [ReportController::class, 'store'])->name('reports.store');
        Route::get('/signalements/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::post('/signalements/{report}/prendre', [ReportController::class, 'take'])->name('reports.take');
        Route::post('/signalements/{report}/commencer', [ReportController::class, 'start'])->name('reports.start');
        Route::post('/signalements/{report}/reporter', [ReportController::class, 'postpone'])->name('reports.postpone');
        Route::post('/signalements/{report}/termine', [ReportController::class, 'finish'])->name('reports.finish');
        Route::post('/signalements/{report}/abandonner', [ReportController::class, 'release'])->name('reports.release');

        Route::get('/demandes/nouvelle', [PickupRequestController::class, 'create'])->name('pickups.create');
        Route::post('/demandes', [PickupRequestController::class, 'store'])->name('pickups.store');
        Route::get('/demandes/{pickup}', [PickupRequestController::class, 'show'])->name('pickups.show');
        Route::post('/demandes/{pickup}/prendre', [PickupRequestController::class, 'take'])->name('pickups.take');
        Route::post('/demandes/{pickup}/reporter', [PickupRequestController::class, 'postpone'])->name('pickups.postpone');
        Route::post('/demandes/{pickup}/termine', [PickupRequestController::class, 'finish'])->name('pickups.finish');
        Route::post('/demandes/{pickup}/abandonner', [PickupRequestController::class, 'release'])->name('pickups.release');

        Route::get('/associations', [AssociationController::class, 'index'])->name('associations.index');
        Route::get('/associations/creer', [AssociationController::class, 'create'])->name('associations.create');
        Route::post('/associations', [AssociationController::class, 'store'])->name('associations.store');
        Route::get('/associations/{association}', [AssociationController::class, 'show'])->name('associations.show');
        Route::post('/associations/{association}/rejoindre', [AssociationController::class, 'join'])->name('associations.join');
        Route::delete('/associations/{association}/quitter', [AssociationController::class, 'leave'])->name('associations.leave');

        // Espace du chef de groupe.
        Route::prefix('associations/{association}/gerer')->name('associations.manage.')->group(function () {
            Route::get('/', [AssociationManagementController::class, 'show'])->name('show');
            Route::patch('/', [AssociationManagementController::class, 'update'])->name('update');
            Route::post('/publications', [AssociationManagementController::class, 'storePost'])->name('posts.store');
            Route::delete('/publications/{post}', [AssociationManagementController::class, 'destroyPost'])->name('posts.destroy');
            Route::post('/adhesions/{membership}/accepter', [AssociationManagementController::class, 'acceptMember'])->name('members.accept');
            Route::post('/adhesions/{membership}/refuser', [AssociationManagementController::class, 'refuseMember'])->name('members.refuse');
        });
    });

    // Citoyens : calendrier des camions par village et signalement d'un problème de camion.
    Route::middleware('role:citoyen')->group(function () {
        Route::get('/camion', [TruckCalendarController::class, 'index'])->name('trucks.index');
        Route::post('/camion/{truck}/probleme', [TruckComplaintController::class, 'store'])->name('trucks.complaints.store');

        // Redevance : payer via SimuPay, revenir du paiement, montrer la preuve au camionneur.
        Route::post('/redevances/{due}/payer', [PaymentController::class, 'pay'])->name('dues.pay');
        Route::get('/redevances/{due}/preuve', [PaymentController::class, 'receipt'])->name('dues.receipt');
        Route::get('/paiements/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    });

    // Agents de collecte : tournée, planning et alertes aux habitants.
    Route::middleware('role:agent')->prefix('agent')->name('agent.')->group(function () {
        Route::get('/tournee', [Agent\RouteController::class, 'index'])->name('route');
        Route::post('/tournee/points/{stop}', [Agent\RouteController::class, 'toggleStop'])->name('stops.toggle');
        Route::get('/planning', [Agent\PlanningController::class, 'index'])->name('planning');
        Route::post('/alertes', [Agent\TruckNoticeController::class, 'store'])->name('notices.store');
        Route::post('/passages/{village}', [Agent\TruckPassageController::class, 'store'])->name('passages.store');
        Route::get('/verifier-paiement', Agent\ReceiptCheckController::class)->name('receipts.check');
    });
});
