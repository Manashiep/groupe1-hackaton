@extends('layouts.admin')

@section('title', 'Itinéraires de collecte')

@include('partials.leaflet')

@section('content')
<form method="GET" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-sm-5 col-md-4">
            <label class="form-label small" for="team">Équipe</label>
            <select id="team" name="team" class="form-select" onchange="this.form.submit()">
                @foreach ($teams as $t)
                    <option value="{{ $t->id }}" @selected($selected?->id === $t->id)>{{ $t->name }} · {{ $t->vehicle_plate }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-3">
            <label class="form-label small" for="date">Date</label>
            <input type="date" id="date" name="date" value="{{ $date->toDateString() }}" class="form-control" onchange="this.form.submit()">
        </div>
        <div class="col-sm-3 col-md-5 text-sm-end small text-muted">
            Ordre de passage par horaire. Distance estimée ×1,3 sur les routes.
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    @foreach ($summary as $row)
        <div class="col-md-4">
            <a href="{{ route('admin.routes.index', ['team' => $row['team']->id, 'date' => $date->toDateString()]) }}" class="card kpi text-decoration-none text-body {{ $selected?->id === $row['team']->id ? 'border-primary' : '' }}">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <span class="kpi-icon text-white" style="background: {{ $row['team']->color }}"><i class="bi bi-truck"></i></span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $row['team']->name }}</div>
                        <div class="small text-muted">{{ $row['total'] }} arrêt(s) · ≈ {{ $row['distance_km'] }} km · {{ $row['done'] }} terminé(s)</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

@if ($route && $route['total'])
    <div class="row g-4">
        <div class="col-xl-8"><div id="map" class="map" style="height: 520px"></div></div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">Feuille de route · {{ $selected->name }}</div>
                <ol class="list-group list-group-flush list-group-numbered small">
                    @foreach ($route['stops'] as $stop)
                        <li class="list-group-item d-flex gap-2 align-items-start">
                            <div class="flex-grow-1 ms-1">
                                <span class="fw-bold tabular">{{ $stop['time'] }}</span> {{ $stop['title'] }}
                                <div class="text-muted">{{ $stop['subtitle'] }}</div>
                            </div>
                            <span class="status bg-{{ $stop['status_color'] }}-subtle text-{{ $stop['status_color'] }}-emphasis">{{ $stop['status_label'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
@else
    <div class="card"><div class="empty"><i class="bi bi-signpost"></i>Aucun arrêt prévu pour cette équipe ce jour-là.</div></div>
@endif
@endsection

@if ($route && $route['total'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => CC.routeMap('map', @js($points), @js($selected->color)));
</script>
@endpush
@endif
