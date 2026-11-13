<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Notifications\CleanNotification;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CollectionComplaintController extends Controller
{
    public function store(Request $request, Collection $collection, Notifier $notifier): RedirectResponse
    {
        if (! $collection->canBeReportedMissing()) {
            return back()->with('error', 'Ce passage ne peut pas encore être signalé comme manqué (attendez une heure après l\'horaire prévu).');
        }

        $data = $request->validate(['comment' => ['nullable', 'string', 'max:500']]);

        $complaint = $collection->complaints()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['comment' => $data['comment'] ?? null],
        );

        if ($complaint->wasRecentlyCreated && $collection->complaints()->count() === 1) {
            $collection->loadMissing('quartier');
            $notifier->staff(new CleanNotification(
                'Collecte signalée non effectuée',
                "Des habitants de {$collection->quartier->name} indiquent que la camionnette n'est pas passée ({$collection->scheduled_date->translatedFormat('l j F')}, {$collection->timeLabel()}).",
                route('admin.collections.index', ['date' => $collection->scheduled_date->toDateString()]),
                'bi-exclamation-triangle',
                'danger',
            ));
        }

        return back()->with('success', 'Merci, le service de collecte est prévenu que la camionnette n\'est pas passée.');
    }
}
