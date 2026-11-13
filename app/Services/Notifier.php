<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Collection;
use App\Models\User;
use App\Notifications\CleanNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Point d'entrée unique pour prévenir les bons destinataires.
 */
class Notifier
{
    public function staff(CleanNotification $notification): void
    {
        Notification::send(
            User::whereIn('role', [Role::Responsable, Role::Admin])->where('is_active', true)->get(),
            $notification,
        );
    }

    public function quartierResidents(int $quartierId, CleanNotification $notification): int
    {
        $residents = User::where('role', Role::Citoyen)->where('quartier_id', $quartierId)->where('is_active', true)->get();
        Notification::send($residents, $notification);

        return $residents->count();
    }

    public function team(?int $teamId, CleanNotification $notification): void
    {
        if (! $teamId) {
            return;
        }
        Notification::send(User::where('role', Role::Agent)->where('team_id', $teamId)->get(), $notification);
    }

    public function user(?User $user, CleanNotification $notification): void
    {
        $user?->notify($notification);
    }

    /** Rappel envoyé la veille aux habitants des quartiers desservis. Retourne le nombre d'habitants prévenus. */
    public function collectionReminders(Carbon $date): int
    {
        $sent = 0;
        Collection::with('quartier')
            ->whereDate('scheduled_date', $date)
            ->where('status', 'programmee')
            ->get()
            ->each(function (Collection $collection) use (&$sent, $date) {
                $when = $date->isTomorrow() ? 'demain' : $date->translatedFormat('l j F');
                $sent += $this->quartierResidents($collection->quartier_id, new CleanNotification(
                    'Rappel de collecte',
                    "La camionnette passe {$when} {$collection->timeLabel()} à {$collection->quartier->name} ({$collection->waste_type->label()}). Sortez vos sacs juste avant le passage.",
                    route('citizen.dashboard'),
                    'bi-truck',
                    'primary',
                ));
            });

        return $sent;
    }
}
