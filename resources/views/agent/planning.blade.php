@extends('layouts.app')

@section('title', 'Planning de la semaine')

@section('content')
<div class="page-head">
    <div class="small-caps">{{ $team?->name ?? 'Aucune équipe' }}</div>
    <h1>Planning des 7 prochains jours</h1>
    <p class="text-muted mb-0">Passages réguliers et interventions affectées à votre équipe.</p>
</div>

@if (! $team)
    <div class="alert alert-warning">Vous n'êtes rattaché à aucune équipe.</div>
@else
    <div class="row g-3">
        @foreach ($days as $day)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('agent.dashboard', ['date' => $day['date']->toDateString()]) }}" class="card h-100 text-decoration-none text-body {{ $day['date']->isToday() ? 'border-warning' : '' }}">
                    <div class="card-header d-flex justify-content-between">
                        <span>{{ ucfirst($day['date']->translatedFormat('l j F')) }}</span>
                        <span class="small text-muted fw-normal">{{ $day['total'] }} arrêt(s) · ≈ {{ $day['distance_km'] }} km</span>
                    </div>
                    <ul class="list-group list-group-flush small">
                        @forelse ($day['stops'] as $stop)
                            <li class="list-group-item d-flex gap-2">
                                <span class="fw-bold tabular">{{ $stop['time'] }}</span>
                                <span class="flex-grow-1">
                                    @if ($stop['type'] === 'intervention')<i class="bi bi-clipboard-check text-warning"></i>@else<i class="bi bi-truck text-primary"></i>@endif
                                    {{ $stop['title'] }}
                                </span>
                                @if ($stop['done'])<i class="bi bi-check-circle-fill text-success"></i>@endif
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Repos — pas de tournée.</li>
                        @endforelse
                    </ul>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection
