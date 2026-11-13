@extends('layouts.app')

@section('title', $quartier->name)

@include('partials.leaflet')

@section('content')
<div class="page-head">
    <nav aria-label="Fil d'Ariane" class="small mb-2"><a href="{{ route('calendar') }}" class="text-decoration-none">Quartiers</a> / {{ $quartier->name }}</nav>
    <div class="d-flex flex-wrap align-items-center gap-4">
        <x-score :data="$index" size="ring" />
        <div class="flex-grow-1">
            <h1 class="mb-1">{{ $quartier->name }}</h1>
            <div class="text-muted">{{ $quartier->ville }} · {{ $quartier->ile }} @if($quartier->population) · ≈ {{ number_format($quartier->population, 0, ',', ' ') }} habitants @endif</div>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="status bg-{{ $index['color'] }}-subtle text-{{ $index['color'] }}-emphasis">Indice de propreté : {{ $index['label'] }}</span>
                <span class="status bg-light text-body border"><i class="bi bi-truck"></i> Collectes réalisées : {{ $index['collection_rate'] !== null ? $index['collection_rate'].' %' : '—' }}</span>
                <span class="status bg-light text-body border"><i class="bi bi-trash3"></i> Dépôts vérifiés ouverts : {{ $index['open'] }}</span>
                <span class="status bg-light text-body border"><i class="bi bi-arrow-repeat"></i> Problèmes vérifiés (30 j) : {{ $index['recent'] }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        @include('partials.next-collection', ['next' => $next, 'quartier' => $quartier])
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">Semaine type</div>
            <div class="card-body">@include('partials.collection-week', ['schedules' => $schedules])</div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header">Signalements récents</div>
            <div id="map" class="map map-sm rounded-0 border-0 border-bottom"></div>
            <div class="list-group list-group-flush">
                @forelse ($recentReports as $report)
                    <div class="list-group-item d-flex gap-3 align-items-center">
                        <div class="report-thumb">
                            @if ($report->photoUrl())<img src="{{ $report->photoUrl() }}" alt="">@else<i class="bi {{ $report->category->icon() }}"></i>@endif
                        </div>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="fw-semibold">{{ $report->category->label() }}</div>
                            <div class="small text-muted text-truncate">{{ $report->address }} · {{ $report->created_at->diffForHumans() }}</div>
                        </div>
                        <x-status :status="$report->status" />
                    </div>
                @empty
                    <div class="empty"><i class="bi bi-emoji-smile"></i>Aucun signalement récent dans ce quartier.</div>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header">Passages récents et à venir</div>
            @include('partials.collection-list', ['collections' => $upcoming])
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">Problèmes vérifiés (90 jours)</div>
            <div class="card-body">
                @forelse ($categoryStats as $label => $total)
                    <div class="d-flex justify-content-between small mb-1"><span>{{ $label }}</span><strong class="tabular">{{ $total }}</strong></div>
                    <div class="progress mb-3" style="height:6px"><div class="progress-bar" style="width: {{ $total / max($categoryStats->max(), 1) * 100 }}%"></div></div>
                @empty
                    <p class="text-muted small mb-0">Aucun problème vérifié sur la période.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header">Historique des interventions</div>
            <ul class="list-group list-group-flush small">
                @forelse ($interventions as $intervention)
                    <li class="list-group-item">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        <strong>{{ $intervention->title }}</strong>
                        <div class="text-muted">{{ $intervention->completed_at?->translatedFormat('j F Y') }} · {{ $intervention->team?->name }}</div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucune intervention terminée pour l'instant.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const map = CC.baseMap('map', { center: [{{ $quartier->latitude }}, {{ $quartier->longitude }}], zoom: 16 });
    const reports = @js($recentReports->map(fn ($r) => ['lat' => $r->latitude, 'lng' => $r->longitude, 'color' => $r->status->hex(), 'label' => $r->category->label()]));
    reports.forEach((r) => L.marker([r.lat, r.lng], { icon: CC.pin(r.color) }).addTo(map).bindPopup(CC.escape(r.label)));
});
</script>
@endpush
