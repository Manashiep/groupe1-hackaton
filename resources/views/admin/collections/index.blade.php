@extends('layouts.admin')

@section('title', 'Collectes du '.$date->translatedFormat('j F'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="btn-group">
        <a class="btn btn-soft" href="{{ route('admin.collections.index', ['date' => $date->copy()->subDay()->toDateString()]) }}" aria-label="Jour précédent"><i class="bi bi-chevron-left"></i></a>
        <a class="btn btn-soft {{ $date->isToday() ? 'active' : '' }}" href="{{ route('admin.collections.index') }}">Aujourd'hui</a>
        <a class="btn btn-soft" href="{{ route('admin.collections.index', ['date' => $date->copy()->addDay()->toDateString()]) }}" aria-label="Jour suivant"><i class="bi bi-chevron-right"></i></a>
    </div>
    <form method="GET" class="d-flex gap-2">
        <input type="date" name="date" value="{{ $date->toDateString() }}" class="form-control" onchange="this.form.submit()" aria-label="Choisir une date">
    </form>
    <div class="d-flex gap-2 ms-auto">
        <form method="POST" action="{{ route('admin.collections.reminders') }}">@csrf
            <button class="btn btn-soft"><i class="bi bi-bell me-1"></i> Rappels de demain</button>
        </form>
        <form method="POST" action="{{ route('admin.collections.generate') }}">@csrf
            <button class="btn btn-soft"><i class="bi bi-arrow-clockwise me-1"></i> Générer 14 jours</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach ($statuses as $status)
        <div class="col-6 col-md">
            <div class="card kpi">
                <div class="card-body py-3">
                    <div class="kpi-label"><i class="bi {{ $status->icon() }} text-{{ $status->color() }}"></i> {{ $status->label() }}</div>
                    <div class="kpi-value">{{ $counts[$status->value] ?? 0 }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-8">
        @forelse ($byTeam as $teamName => $items)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:{{ $items->first()->team?->color }}"></span> {{ $teamName }} <span class="text-muted fw-normal small">· {{ $items->first()->team?->vehicle_plate }}</span></span>
                    <a href="{{ route('admin.routes.index', ['team' => $items->first()->team_id, 'date' => $date->toDateString()]) }}" class="small fw-semibold text-decoration-none">Itinéraire</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            @foreach ($items as $collection)
                                <tr>
                                    <td class="text-nowrap fw-bold tabular" style="width: 110px">{{ $collection->timeLabel() }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $collection->quartier->name }}</div>
                                        <div class="small text-muted">
                                            {{ $collection->waste_type->label() }}
                                            @if ($collection->completed_at) · confirmée {{ $collection->completed_at->format('H\hi') }} par {{ $collection->confirmer?->name }} @endif
                                            @if ($collection->agent_note) · <em>{{ $collection->agent_note }}</em>@endif
                                        </div>
                                        @if ($collection->complaints_count)
                                            <div class="small text-danger"><i class="bi bi-flag-fill"></i> {{ $collection->complaints_count }} signalement(s) d'habitants</div>
                                        @endif
                                    </td>
                                    <td><x-status :status="$collection->status" /></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-soft btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">Gérer</button>
                                            <div class="dropdown-menu dropdown-menu-end p-3 shadow" style="width: 290px">
                                                <form method="POST" action="{{ route('admin.collections.update', $collection) }}">
                                                    @csrf @method('PATCH')
                                                    <label class="form-label small">Corriger le statut</label>
                                                    <select name="status" class="form-select form-select-sm mb-2">
                                                        @foreach ($statuses as $s)
                                                            <option value="{{ $s->value }}" @selected($collection->status === $s)>{{ $s->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-primary btn-sm w-100">Enregistrer</button>
                                                </form>
                                                @if ($collection->status !== \App\Enums\CollectionStatus::Effectuee)
                                                    <hr>
                                                    <form method="POST" action="{{ route('admin.collections.reschedule', $collection) }}">
                                                        @csrf
                                                        <label class="form-label small">Programmer un rattrapage</label>
                                                        <div class="d-flex gap-2 mb-2">
                                                            <input type="date" name="date" value="{{ max(today(), $collection->scheduled_date->copy()->addDay())->toDateString() }}" min="{{ today()->toDateString() }}" class="form-control form-control-sm">
                                                            <input type="time" name="start_time" value="{{ substr($collection->start_time, 0, 5) }}" class="form-control form-control-sm">
                                                        </div>
                                                        <button class="btn btn-sun btn-sm w-100"><i class="bi bi-arrow-repeat me-1"></i> Programmer et prévenir</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card"><div class="empty"><i class="bi bi-calendar-x"></i>Aucun passage programmé ce jour-là.</div></div>
        @endforelse
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-flag text-danger me-1"></i> Passages manqués signalés (7 jours)</div>
            <ul class="list-group list-group-flush small">
                @forelse ($complaints as $complaint)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $complaint->collection->quartier->name }}</strong>
                            <span class="text-muted">{{ $complaint->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-muted">Passage du {{ $complaint->collection->scheduled_date->translatedFormat('l j F') }} · {{ $complaint->user->name }}</div>
                        @if ($complaint->comment)<div class="mt-1">« {{ $complaint->comment }} »</div>@endif
                        <a href="{{ route('admin.collections.index', ['date' => $complaint->collection->scheduled_date->toDateString()]) }}" class="text-decoration-none">Voir la journée</a>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucun signalement de passage manqué.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
