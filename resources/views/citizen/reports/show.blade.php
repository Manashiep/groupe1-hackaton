@extends('layouts.app')

@section('title', $report->reference())

@include('partials.leaflet')

@section('content')
<div class="page-head d-md-flex justify-content-between align-items-end gap-3">
    <div>
        <a href="{{ route('citizen.reports.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Mes signalements</a>
        <h1 class="mt-2">{{ $report->category->label() }}</h1>
        <div class="text-muted">{{ $report->reference() }} · envoyé {{ $report->created_at->translatedFormat('l j F Y à H\hi') }}</div>
    </div>
    <x-status :status="$report->status" class="fs-6 mt-3 mt-md-0" />
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-body">
                <div class="photo-frame mb-3">
                    @if ($report->photoUrl())
                        <img src="{{ $report->photoUrl() }}" alt="Photo du signalement">
                    @else
                        <span class="text-center"><i class="bi {{ $report->category->icon() }} fs-1 d-block"></i>Pas de photo</span>
                    @endif
                </div>
                <p class="mb-2">{{ $report->description }}</p>
                <div class="small text-muted"><i class="bi bi-geo-alt"></i> {{ $report->address ?? 'Position indiquée sur la carte' }} · {{ $report->quartier?->fullName() }}</div>
            </div>
        </div>
        <div id="map" class="map map-sm"></div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header">Suivi du traitement</div>
            <div class="card-body">
                <ul class="timeline">
                    @foreach ($report->events as $event)
                        <li>
                            <span class="t-icon bg-{{ $event->status?->color() ?? 'secondary' }}-subtle text-{{ $event->status?->color() ?? 'secondary' }}-emphasis"><i class="bi {{ $event->status?->icon() ?? 'bi-dot' }}"></i></span>
                            <div class="fw-semibold">{{ $event->status?->label() }}</div>
                            @if ($event->note)<div class="small">{{ $event->note }}</div>@endif
                            <div class="small text-muted">{{ $event->created_at->translatedFormat('j M Y, H\hi') }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @foreach ($report->interventions->where('completion_photo_path') as $intervention)
            <div class="card">
                <div class="card-header">Après nettoyage</div>
                <img src="{{ $intervention->completionPhotoUrl() }}" class="img-fluid" alt="Photo après nettoyage">
                @if ($intervention->completion_note)<div class="card-body small">{{ $intervention->completion_note }}</div>@endif
            </div>
        @endforeach
        @if ($report->points_awarded)
            <div class="alert alert-warning d-flex gap-2 align-items-center mt-3 mb-0"><i class="bi bi-stars fs-5"></i> Ce signalement vous a rapporté 10 points citoyens.</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const map = CC.baseMap('map', { center: [{{ $report->latitude }}, {{ $report->longitude }}], zoom: 17 });
    L.marker([{{ $report->latitude }}, {{ $report->longitude }}], { icon: CC.pin('{{ $report->status->hex() }}', 22) }).addTo(map);
});
</script>
@endpush
