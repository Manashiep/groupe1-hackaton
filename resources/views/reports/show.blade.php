@extends('layouts.app')

@section('title', 'Dépôt sauvage')

@include('partials.leaflet')

@use('App\Enums\ReportStatus')

@section('content')
<div class="media-hero">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('map') }}" class="btn btn-ghost btn-sm back-btn"><i class="bi bi-arrow-left"></i> Retour</a>
    @if ($report->photoUrl())
        <img src="{{ $report->photoUrl() }}" alt="Photo du dépôt sauvage">
    @else
        <div class="no-photo"><i class="bi bi-trash3"></i></div>
    @endif
</div>

<div class="page">
    @include('partials.flash')

    <span class="pill st-{{ $report->status->value }}"><span class="dot"></span>{{ $report->status->getLabel() }}</span>
    <h1 class="h3 mt-2 mb-1">{{ $report->description ?: 'Dépôt sauvage' }}</h1>
    <p class="text-muted mb-3"><i class="bi bi-geo-alt"></i> {{ $report->village?->name ?? 'Ngazidja' }} · signalé {{ $report->created_at->diffForHumans() }}</p>

    @if ($report->taken_by_id)
        <div class="panel d-flex gap-3 align-items-center mb-3">
            @if ($report->association)
                <span class="asso-avatar" style="background: {{ $report->association->color }}">{{ $report->association->initials() }}</span>
            @else
                <span class="avatar">{{ $report->taker?->initials() }}</span>
            @endif
            <div>
                <div class="fw-bold">
                    @if ($report->status === ReportStatus::Nettoye) Nettoyé par @else Pris en charge par @endif
                    @if ($report->association)<a href="{{ route('associations.show', $report->association) }}">{{ $report->association->name }}</a>@else{{ $report->taker?->shortName() }}@endif
                </div>
                <div class="row-sub">
                    @if ($report->status === ReportStatus::Nettoye)
                        {{ $report->cleaned_at?->translatedFormat('l j F') }}
                    @else
                        {{ $report->cleanupLabel() }}
                    @endif
                </div>
                @if ($report->taker_note)<div class="small mt-1">« {{ $report->taker_note }} »</div>@endif
            </div>
        </div>
    @endif

    @if ($report->status === ReportStatus::Rejete && $report->admin_note)
        <div class="callout mb-3"><i class="bi bi-info-circle-fill"></i><div>{{ $report->admin_note }}</div></div>
    @endif

    <div id="map" class="map"></div>

    @include('partials.take-charge', [
        'item' => $report,
        'isOpen' => $report->status === ReportStatus::Nouveau,
        'isDone' => $report->status === ReportStatus::Nettoye,
        'takeRoute' => route('reports.take', $report),
        'plannedLabel' => $report->cleanupLabel(),
        'plannedAt' => $report->cleanup_at,
        'routes' => [
            'start' => $report->status === ReportStatus::PrisEnCharge ? route('reports.start', $report) : null,
            'postpone' => route('reports.postpone', $report),
            'finish' => route('reports.finish', $report),
            'release' => route('reports.release', $report),
        ],
    ])
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const at = [{{ $report->latitude }}, {{ $report->longitude }}];
    const map = CC.baseMap('map', { center: at, zoom: 17 });
    L.marker(at, { icon: CC.pin(@js($report->status->hex())) }).addTo(map);
});
</script>
@endpush
