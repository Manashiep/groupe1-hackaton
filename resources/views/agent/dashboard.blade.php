@extends('layouts.app')

@section('title', 'Ma tournée')

@include('partials.leaflet')

@use('App\Enums\CollectionStatus')
@use('App\Enums\InterventionStatus')
@php
    $actionable = ! $date->isFuture();
    $currentFound = false;
@endphp

@section('content')
<div class="page-head">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="small-caps">{{ $team?->name ?? 'Aucune équipe' }} @if($team?->vehicle_plate) · camionnette {{ $team->vehicle_plate }} @endif</div>
            <h1>{{ $date->isToday() ? 'Tournée du jour' : 'Tournée du '.$date->translatedFormat('l j F') }}</h1>
            <div class="text-muted">{{ ucfirst($date->translatedFormat('l j F Y')) }}</div>
        </div>
        <div class="btn-group" role="group" aria-label="Changer de jour">
            <a class="btn btn-soft" href="{{ route('agent.dashboard', ['date' => $date->copy()->subDay()->toDateString()]) }}" aria-label="Jour précédent"><i class="bi bi-chevron-left"></i></a>
            <a class="btn btn-soft {{ $date->isToday() ? 'active' : '' }}" href="{{ route('agent.dashboard') }}">Aujourd'hui</a>
            <a class="btn btn-soft" href="{{ route('agent.dashboard', ['date' => $date->copy()->addDay()->toDateString()]) }}" aria-label="Jour suivant"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
</div>

@if (! $team)
    <div class="alert alert-warning">Vous n'êtes rattaché à aucune équipe. Demandez au responsable de vous affecter à une camionnette.</div>
@elseif ($route['total'] === 0)
    <div class="card"><div class="empty"><i class="bi bi-cup-hot"></i>Pas de tournée ni de mission prévue ce jour-là.</div></div>
@else
    <div class="row g-4">
        <div class="col-lg-5 order-lg-2">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span>{{ $route['done'] }} / {{ $route['total'] }} arrêts terminés</span>
                        <span class="text-muted">≈ {{ $route['distance_km'] }} km</span>
                    </div>
                    <div class="progress" style="height:10px"><div class="progress-bar" style="width: {{ $route['total'] ? $route['done'] / $route['total'] * 100 : 0 }}%"></div></div>
                </div>
            </div>
            <div id="map" class="map" style="height: 380px"></div>
            <div class="small text-muted mt-2"><span class="text-primary">●</span> collecte · <span class="text-warning">●</span> intervention · <span class="text-success">●</span> terminé</div>
        </div>

        <div class="col-lg-7 order-lg-1">
            <div class="card">
                @foreach ($route['stops'] as $i => $stop)
                    @php
                        $model = $stop['model'];
                        $isCurrent = ! $stop['done'] && ! $currentFound;
                        if ($isCurrent) { $currentFound = true; }
                    @endphp
                    <div class="stop {{ $stop['done'] ? 'done' : '' }} {{ $isCurrent && $actionable ? 'current' : '' }}">
                        <div class="stop-n">@if($stop['done'])<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif</div>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <span class="stop-time">{{ $stop['time'] }}</span>
                                    <span class="fw-semibold ms-1">{{ $stop['title'] }}</span>
                                    <div class="small text-muted">{{ $stop['subtitle'] }}</div>
                                </div>
                                <span class="status bg-{{ $stop['status_color'] }}-subtle text-{{ $stop['status_color'] }}-emphasis align-self-start">{{ $stop['status_label'] }}</span>
                            </div>

                            @if ($stop['type'] === 'collection')
                                @if ($model->complaints_count)
                                    <div class="small text-danger mt-1"><i class="bi bi-flag-fill"></i> {{ $model->complaints_count }} habitant(s) signalent que le passage n'a pas eu lieu</div>
                                @endif
                                @if ($model->agent_note)<div class="small text-muted mt-1"><i class="bi bi-chat-left-text"></i> {{ $model->agent_note }}</div>@endif

                                @if ($actionable)
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        @if ($model->status === CollectionStatus::Programmee)
                                            <form method="POST" action="{{ route('agent.collections.start', $model) }}">@csrf
                                                <button class="btn btn-primary {{ $isCurrent ? 'btn-xl' : 'btn-sm' }}"><i class="bi bi-play-fill"></i> Démarrer le passage</button>
                                            </form>
                                        @endif
                                        @if (in_array($model->status, [CollectionStatus::Programmee, CollectionStatus::EnCours], true))
                                            <form method="POST" action="{{ route('agent.collections.complete', $model) }}">@csrf
                                                <button class="btn btn-success {{ $model->status === CollectionStatus::EnCours ? 'btn-xl' : 'btn-sm' }}"><i class="bi bi-check2-circle"></i> Collecte effectuée</button>
                                            </form>
                                            <button class="btn btn-link btn-sm text-danger text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#fail-{{ $model->id }}">Impossible de passer ?</button>
                                        @endif
                                    </div>
                                    <div class="collapse mt-2" id="fail-{{ $model->id }}">
                                        <form method="POST" action="{{ route('agent.collections.fail', $model) }}" class="d-flex gap-2">@csrf
                                            <input name="agent_note" class="form-control form-control-sm" placeholder="Raison : panne, route bloquée…" required>
                                            <button class="btn btn-danger btn-sm text-nowrap">Signaler</button>
                                        </form>
                                    </div>
                                @endif
                            @else
                                @if ($model->notes)<div class="small mt-1"><i class="bi bi-info-circle text-primary"></i> {{ \Illuminate\Support\Str::limit($model->notes, 160) }}</div>@endif
                                <div class="d-flex flex-wrap gap-2 mt-2 small">
                                    <span class="status bg-{{ $model->priorityColor() }}-subtle text-{{ $model->priorityColor() }}-emphasis">Priorité {{ mb_strtolower($model->priorityLabel()) }}</span>
                                    @if ($model->report?->photoUrl())
                                        <a href="{{ $model->report->photoUrl() }}" target="_blank" rel="noopener" class="text-decoration-none"><i class="bi bi-image"></i> Photo du signalement</a>
                                    @endif
                                    @if ($stop['lat'])
                                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $stop['lat'] }},{{ $stop['lng'] }}" target="_blank" rel="noopener" class="text-decoration-none"><i class="bi bi-sign-turn-right"></i> Itinéraire GPS</a>
                                    @endif
                                </div>

                                @if ($actionable)
                                    @if ($model->status === InterventionStatus::Planifiee)
                                        <form method="POST" action="{{ route('agent.interventions.start', $model) }}" class="mt-2">@csrf
                                            <button class="btn btn-primary {{ $isCurrent ? 'btn-xl' : 'btn-sm' }}"><i class="bi bi-play-fill"></i> Démarrer l'intervention</button>
                                        </form>
                                    @elseif ($model->status === InterventionStatus::EnCours)
                                        <form method="POST" action="{{ route('agent.interventions.complete', $model) }}" enctype="multipart/form-data" class="mt-2 p-3 rounded-3 bg-light">@csrf
                                            <label class="form-label small" for="note-{{ $model->id }}">Compte rendu</label>
                                            <input id="note-{{ $model->id }}" name="completion_note" class="form-control form-control-sm mb-2" placeholder="Ex. : 12 sacs évacués, zone propre.">
                                            <label class="form-label small" for="photo-{{ $model->id }}">Photo après nettoyage</label>
                                            <input id="photo-{{ $model->id }}" type="file" name="completion_photo" accept="image/*" class="form-control form-control-sm mb-2">
                                            <button class="btn btn-success btn-xl w-100"><i class="bi bi-check2-circle"></i> Zone nettoyée</button>
                                        </form>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
@endsection

@if ($team && $route['total'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => CC.routeMap('map', @js($points), @js($team->color)));
</script>
@endpush
@endif
