@extends('layouts.app')

@section('title', 'Mes signalements')

@section('content')
<div class="page">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Mes signalements</h1>
        <a href="{{ route('reports.create') }}" class="btn btn-sun btn-sm"><i class="bi bi-plus-lg"></i> Signaler</a>
    </div>

    @include('partials.flash')

    @forelse ($reports as $report)
        <div class="item">
            <div class="thumb">
                @if ($report->photoUrl())<img src="{{ $report->photoUrl() }}" alt="">@else<i class="bi bi-trash3"></i>@endif
            </div>
            <div class="flex-grow-1" style="min-width: 0">
                <span class="pill" style="background: {{ $report->status->hex() }}1a; color: {{ $report->status->hex() }}">
                    <span class="dot" style="background: {{ $report->status->hex() }}"></span>{{ $report->status->getLabel() }}
                </span>
                @if ($report->description)<div class="mt-1 text-truncate">{{ $report->description }}</div>@endif
                <div class="small text-muted">Envoyé {{ $report->created_at->diffForHumans() }}</div>
                @if ($report->cleanupLabel() && in_array($report->status, [\App\Enums\ReportStatus::PrisEnCharge, \App\Enums\ReportStatus::EnCours], true))
                    <div class="small fw-bold mt-1"><i class="bi bi-truck"></i> {{ $report->truck?->name }} · {{ $report->cleanupLabel() }}</div>
                @elseif ($report->status === \App\Enums\ReportStatus::Rejete && $report->admin_note)
                    <div class="small text-muted mt-1">{{ $report->admin_note }}</div>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">
            <i class="bi bi-flag"></i>
            Vous n'avez encore rien signalé.
            <div class="mt-3"><a href="{{ route('reports.create') }}" class="btn btn-primary">Faire un signalement</a></div>
        </div>
    @endforelse
</div>
@endsection
