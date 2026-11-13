@extends('layouts.admin')

@section('title', 'Interventions')

@section('content')
<form class="card mb-3" method="GET">
    <div class="card-body row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label small" for="status">Statut</label>
            <select id="status" name="status" class="form-select">
                <option value="">Tous</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="team">Équipe</label>
            <select id="team" name="team" class="form-select">
                <option value="">Toutes</option>
                @foreach ($teams as $t)
                    <option value="{{ $t->id }}" @selected(($filters['team'] ?? '') == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small" for="date">Date</label>
            <input type="date" id="date" name="date" value="{{ $filters['date'] ?? '' }}" class="form-control">
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">Filtrer</button>
            <a href="{{ route('admin.interventions.index') }}" class="btn btn-soft" aria-label="Réinitialiser"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Intervention</th><th>Équipe</th><th>Prévue</th><th>Priorité</th><th>Statut</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($interventions as $intervention)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $intervention->title }}</div>
                            <div class="small text-muted">
                                {{ $intervention->kindLabel() }}
                                @if ($intervention->report) · <a href="{{ route('admin.reports.show', $intervention->report) }}">{{ $intervention->report->reference() }}</a>@endif
                                @if ($intervention->pickupRequest) · {{ $intervention->pickupRequest->reference() }}@endif
                            </div>
                        </td>
                        <td class="small"><span class="legend-key" style="display:inline-block;width:9px;height:9px;border-radius:50%;background:{{ $intervention->team?->color }}"></span> {{ $intervention->team?->name ?? '—' }}</td>
                        <td class="small text-nowrap">
                            {{ $intervention->scheduled_at->translatedFormat('D j M, H\hi') }}
                            @if ($intervention->completed_at)<div class="text-success">terminée {{ $intervention->completed_at->format('H\hi') }}</div>@endif
                        </td>
                        <td><span class="status bg-{{ $intervention->priorityColor() }}-subtle text-{{ $intervention->priorityColor() }}-emphasis">{{ $intervention->priorityLabel() }}</span></td>
                        <td><x-status :status="$intervention->status" /></td>
                        <td class="text-end">
                            @if ($intervention->status === \App\Enums\InterventionStatus::Planifiee)
                                <div class="dropdown">
                                    <button class="btn btn-soft btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">Modifier</button>
                                    <div class="dropdown-menu dropdown-menu-end p-3 shadow" style="width: 280px">
                                        <form method="POST" action="{{ route('admin.interventions.update', $intervention) }}">
                                            @csrf @method('PATCH')
                                            <label class="form-label small">Équipe</label>
                                            <select name="team_id" class="form-select form-select-sm mb-2">
                                                @foreach ($teams as $t)
                                                    <option value="{{ $t->id }}" @selected($intervention->team_id === $t->id)>{{ $t->name }}</option>
                                                @endforeach
                                            </select>
                                            <label class="form-label small">Date et heure</label>
                                            <input type="datetime-local" name="scheduled_at" value="{{ $intervention->scheduled_at->format('Y-m-d\TH:i') }}" class="form-control form-control-sm mb-2">
                                            <button class="btn btn-primary btn-sm w-100">Enregistrer</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.interventions.update', $intervention) }}" class="mt-2" onsubmit="return confirm('Annuler cette intervention ?')">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="cancel" value="1">
                                            <button class="btn btn-link btn-sm text-danger p-0">Annuler l'intervention</button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><i class="bi bi-clipboard"></i>Aucune intervention.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $interventions->links() }}</div>
@endsection
