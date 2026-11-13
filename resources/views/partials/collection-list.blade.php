{{-- Liste des passages datés : $collections, $complainedIds (optionnel) --}}
@php
    $complainedIds = $complainedIds ?? [];
    $canComplain = auth()->user()?->isCitizen();
@endphp
<div>
    @forelse ($collections as $collection)
        <div class="day-row {{ $collection->isToday() ? 'is-today' : '' }} {{ $collection->scheduled_date->isPast() && ! $collection->isToday() ? 'opacity-75' : '' }}">
            <div class="day-chip">
                <div class="d">{{ $collection->scheduled_date->translatedFormat('D') }}</div>
                <div class="n">{{ $collection->scheduled_date->format('d') }}</div>
            </div>
            <div class="flex-grow-1" style="min-width:0">
                <div class="fw-semibold">
                    {{ $collection->timeLabel() }}
                    @if ($collection->isToday())<span class="badge text-bg-warning ms-1">Aujourd'hui</span>@endif
                    @if ($collection->agent_note === 'Passage de rattrapage')<span class="badge text-bg-info ms-1">Rattrapage</span>@endif
                </div>
                <div class="small text-muted text-truncate">
                    <i class="bi {{ $collection->waste_type->icon() }}"></i> {{ $collection->waste_type->label() }}
                    @if ($collection->team) · {{ $collection->team->name }} @endif
                    @if ($collection->completed_at) · confirmée à {{ $collection->completed_at->format('H\hi') }} @endif
                </div>
            </div>
            <div class="text-end d-flex flex-column align-items-end gap-1">
                <x-status :status="$collection->status" />
                @if ($collection->canBeReportedMissing())
                    @if (in_array($collection->id, $complainedIds))
                        <span class="small text-muted"><i class="bi bi-check2"></i> Retard signalé</span>
                    @elseif ($canComplain)
                        <form method="POST" action="{{ route('citizen.collections.complain', $collection) }}" onsubmit="return confirm('Confirmer que la camionnette n\'est pas passée ?')">
                            @csrf
                            <button class="btn btn-link btn-sm p-0 text-danger text-decoration-none small"><i class="bi bi-flag"></i> Pas passée ?</button>
                        </form>
                    @elseif (! auth()->check())
                        <a href="{{ route('login') }}" class="small text-danger text-decoration-none"><i class="bi bi-flag"></i> Pas passée ?</a>
                    @endif
                @endif
            </div>
        </div>
    @empty
        <div class="empty"><i class="bi bi-calendar-x"></i>Aucun passage sur cette période.</div>
    @endforelse
</div>
