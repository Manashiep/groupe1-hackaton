@extends('layouts.admin')

@section('title', 'Équipes & véhicules')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted small mb-0">Chaque équipe dispose d'une camionnette et d'un secteur de collecte.</p>
    <a href="{{ route('admin.teams.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Nouvelle équipe</a>
</div>

@if ($unassigned->isNotEmpty())
    <div class="alert alert-warning small"><i class="bi bi-person-exclamation me-1"></i> Agents sans équipe : {{ $unassigned->pluck('name')->join(', ') }}.</div>
@endif

<div class="row g-3">
    @foreach ($teams as $team)
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="kpi-icon text-white" style="background: {{ $team->color }}; width:44px; height:44px; border-radius:12px; display:grid; place-items:center"><i class="bi bi-truck fs-5"></i></span>
                        <div class="flex-grow-1">
                            <h2 class="h5 mb-0">{{ $team->name }}</h2>
                            <div class="small text-muted">Camionnette {{ $team->vehicle_plate ?? '—' }}</div>
                        </div>
                        <a href="{{ route('admin.teams.edit', $team) }}" class="btn btn-soft btn-sm">Modifier</a>
                    </div>
                    <div class="small-caps mb-1">Agents</div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @forelse ($team->agents as $agent)
                            <span class="d-inline-flex align-items-center gap-2 small"><span class="avatar" style="width:28px;height:28px;font-size:.7rem">{{ $agent->initials() }}</span>{{ $agent->name }}</span>
                        @empty
                            <span class="small text-muted">Aucun agent</span>
                        @endforelse
                    </div>
                    <div class="small-caps mb-1">Secteur</div>
                    <div class="small">{{ $team->schedules->pluck('quartier.name')->unique()->join(', ') ?: '—' }}</div>
                    <div class="small text-muted mt-2">{{ $team->schedules->count() }} passages / semaine · {{ $team->open_interventions }} intervention(s) en cours</div>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <a href="{{ route('admin.routes.index', ['team' => $team->id]) }}" class="btn btn-soft btn-sm"><i class="bi bi-signpost-split"></i> Itinéraire du jour</a>
                    <a href="{{ route('admin.schedules.index', ['team' => $team->id]) }}" class="btn btn-soft btn-sm"><i class="bi bi-calendar-week"></i> Calendrier</a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
