@extends('layouts.admin')

@section('title', 'Signalements')

@section('content')
@php
    $tabs = ['' => ['Tous', $counts->sum()], 'ouverts' => ['Ouverts', collect(\App\Enums\ReportStatus::open())->sum(fn ($s) => $counts[$s->value] ?? 0)]];
    foreach (\App\Enums\ReportStatus::cases() as $s) { $tabs[$s->value] = [$s->label(), $counts[$s->value] ?? 0]; }
    $current = $filters['status'] ?? '';
@endphp

<ul class="nav nav-pills flex-nowrap overflow-auto gap-1 mb-3 pb-1">
    @foreach ($tabs as $value => [$label, $count])
        <li class="nav-item">
            <a class="nav-link text-nowrap {{ $current === $value ? 'active' : '' }}" href="{{ route('admin.reports.index', array_filter([...$filters, 'status' => $value])) }}">
                {{ $label }} <span class="badge rounded-pill {{ $current === $value ? 'bg-white text-primary' : 'text-bg-light' }} ms-1">{{ $count }}</span>
            </a>
        </li>
    @endforeach
</ul>

<form class="card mb-3" method="GET">
    <div class="card-body row g-2 align-items-end">
        <input type="hidden" name="status" value="{{ $current }}">
        <div class="col-md-4">
            <label class="form-label small" for="q">Recherche</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Référence, description, repère…">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="quartier">Quartier</label>
            <select id="quartier" name="quartier" class="form-select">
                <option value="">Tous</option>
                @foreach ($quartiers as $q)
                    <option value="{{ $q->id }}" @selected(($filters['quartier'] ?? '') == $q->id)>{{ $q->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="category">Type</label>
            <select id="category" name="category" class="form-select">
                <option value="">Tous</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->value }}" @selected(($filters['category'] ?? '') === $c->value)>{{ $c->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">Filtrer</button>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-soft" aria-label="Réinitialiser"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Signalement</th><th>Quartier</th><th>Auteur</th><th>Reçu</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>
                            <div class="d-flex gap-3 align-items-center">
                                <div class="report-thumb" style="width:48px;height:48px">
                                    @if ($report->photoUrl())<img src="{{ $report->photoUrl() }}" alt="">@else<i class="bi {{ $report->category->icon() }}"></i>@endif
                                </div>
                                <div style="min-width:0; max-width: 340px">
                                    <div class="fw-semibold">{{ $report->category->label() }}</div>
                                    <div class="small text-muted text-truncate">{{ $report->reference() }} · {{ $report->description }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $report->quartier?->name }}</td>
                        <td class="small">{{ $report->user?->name ?? '—' }}</td>
                        <td class="small text-nowrap">{{ $report->created_at->translatedFormat('j M, H\hi') }}</td>
                        <td><x-status :status="$report->status" /></td>
                        <td class="text-end"><a href="{{ route('admin.reports.show', $report) }}" class="btn btn-soft btn-sm">Traiter</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><i class="bi bi-inbox"></i>Aucun signalement ne correspond.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $reports->links() }}</div>
@endsection
