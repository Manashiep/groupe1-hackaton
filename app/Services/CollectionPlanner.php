<?php

namespace App\Services;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\CollectionSchedule;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Transforme le calendrier récurrent (CollectionSchedule) en passages datés (Collection).
 */
class CollectionPlanner
{
    public const HORIZON_DAYS = 14;

    /** Crée les passages manquants entre deux dates. Retourne le nombre de passages créés. */
    public function generate(Carbon $from, Carbon $to): int
    {
        $schedules = CollectionSchedule::where('is_active', true)->get();
        $created = 0;

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $date) {
            foreach ($schedules->where('day_of_week', $date->dayOfWeekIso) as $schedule) {
                $collection = Collection::firstOrCreate(
                    ['collection_schedule_id' => $schedule->id, 'scheduled_date' => $date->toDateString()],
                    [
                        'quartier_id' => $schedule->quartier_id,
                        'team_id' => $schedule->team_id,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'waste_type' => $schedule->waste_type,
                        'status' => CollectionStatus::Programmee,
                    ],
                );
                $created += $collection->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $created;
    }

    /** Garantit que les passages des 14 prochains jours existent (au plus une fois par jour). */
    public function ensureUpcoming(): void
    {
        Cache::remember('collections-generated-'.today()->toDateString(), now()->endOfDay(), function () {
            $this->generate(today(), today()->addDays(self::HORIZON_DAYS));

            return true;
        });
    }

    /** Régénère les passages futurs d'un créneau après modification du calendrier. */
    public function refreshSchedule(CollectionSchedule $schedule): void
    {
        $schedule->collections()
            ->whereDate('scheduled_date', '>=', today())
            ->where('status', CollectionStatus::Programmee)
            ->delete();

        Cache::forget('collections-generated-'.today()->toDateString());
        $this->ensureUpcoming();
    }
}
