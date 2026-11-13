{{-- Carte « Prochain passage » : $next (Collection|null), $quartier --}}
<div class="card next-collection h-100">
    <div class="card-body">
        <i class="bi bi-truck truck" aria-hidden="true"></i>
        <div class="small-caps" style="color:#8fb3ad">Prochain passage · {{ $quartier->name }}</div>
        @if ($next)
            @php($starts = $next->startsAt())
            <div class="when mt-2">
                @if ($next->isToday()) Aujourd'hui @elseif ($next->scheduled_date->isTomorrow()) Demain @else {{ ucfirst($next->scheduled_date->translatedFormat('l j F')) }} @endif
                <span class="d-block">{{ $next->timeLabel() }}</span>
            </div>
            <div class="mt-2 countdown">
                @if ($next->status === \App\Enums\CollectionStatus::EnCours)
                    <i class="bi bi-broadcast"></i> La camionnette est en ce moment dans le quartier
                @elseif ($starts->isPast())
                    <i class="bi bi-hourglass-split"></i> Passage prévu à {{ $starts->format('H\hi') }}, en attente de confirmation
                @else
                    <i class="bi bi-clock"></i> {{ ucfirst($starts->diffForHumans(['parts' => 2])) }}
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3 small">
                <span class="badge rounded-pill text-bg-light"><i class="bi {{ $next->waste_type->icon() }} me-1"></i>{{ $next->waste_type->label() }}</span>
                @if ($next->team)
                    <span class="badge rounded-pill bg-white bg-opacity-10 text-white">{{ $next->team->name }} · {{ $next->team->vehicle_plate }}</span>
                @endif
                <x-status :status="$next->status" />
            </div>
            <p class="small mt-3 mb-0" style="color:#a9bcc4">Sortez vos sacs fermés juste avant le passage, pas la veille : cela évite qu'ils soient éventrés par les animaux.</p>
        @else
            <div class="when mt-2">Aucun passage programmé</div>
            <p class="small mt-2 mb-0" style="color:#a9bcc4">Aucune collecte n'est prévue dans les 14 prochains jours pour ce quartier.</p>
        @endif
    </div>
</div>
