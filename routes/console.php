<?php

use App\Services\CollectionPlanner;
use App\Services\Notifier;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('collectes:generer {--jours=14}', function (CollectionPlanner $planner) {
    $days = (int) $this->option('jours');
    $created = $planner->generate(today(), today()->addDays($days));
    $this->info("{$created} passage(s) créé(s) pour les {$days} prochains jours.");
})->purpose('Génère les passages de camionnettes à partir du calendrier de collecte');

Artisan::command('collectes:rappels', function (Notifier $notifier) {
    $sent = $notifier->collectionReminders(today()->addDay());
    $this->info("{$sent} habitant(s) prévenu(s) des collectes de demain.");
})->purpose('Prévient les habitants la veille du passage de la camionnette');

// Planification (lancer « php artisan schedule:work » en local, ou une tâche cron en production).
Schedule::command('collectes:generer')->dailyAt('00:10');
Schedule::command('collectes:rappels')->dailyAt('18:00');
