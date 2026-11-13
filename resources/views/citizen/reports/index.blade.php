@extends('layouts.app')

@section('title', 'Mes signalements')

@section('content')
<div class="page-head d-flex justify-content-between align-items-end gap-3">
    <div>
        <h1>Mes signalements</h1>
        <p class="text-muted mb-0">Suivez le traitement de chaque dépôt que vous avez signalé.</p>
    </div>
    <a href="{{ route('citizen.reports.create') }}" class="btn btn-sun"><i class="bi bi-camera-fill me-1"></i> Nouveau</a>
</div>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse ($reports as $report)
            <a href="{{ route('citizen.reports.show', $report) }}" class="list-group-item list-group-item-action d-flex gap-3 align-items-center py-3">
                <div class="report-thumb">
                    @if ($report->photoUrl())<img src="{{ $report->photoUrl() }}" alt="">@else<i class="bi {{ $report->category->icon() }}"></i>@endif
                </div>
                <div class="flex-grow-1" style="min-width:0">
                    <div class="fw-semibold">{{ $report->category->label() }} <span class="text-muted small fw-normal">· {{ $report->reference() }}</span></div>
                    <div class="small text-muted text-truncate">{{ $report->description }}</div>
                    <div class="small text-muted">{{ $report->quartier?->name }} · {{ $report->created_at->translatedFormat('j F Y à H\hi') }}</div>
                </div>
                <div class="text-end">
                    <x-status :status="$report->status" />
                    @if ($report->points_awarded)<div class="small text-warning-emphasis mt-1 fw-semibold">+10 pts</div>@endif
                </div>
            </a>
        @empty
            <div class="empty"><i class="bi bi-camera"></i>Aucun signalement pour l'instant.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $reports->links() }}</div>
@endsection
