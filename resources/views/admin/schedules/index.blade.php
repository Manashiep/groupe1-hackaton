@extends('layouts.admin')

@section('title', 'Calendrier de collecte')

@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <span class="small text-muted me-1">Filtrer :</span>
            <a href="{{ route('admin.schedules.index') }}" class="btn btn-sm {{ ! $selectedTeam ? 'btn-primary' : 'btn-soft' }}">Toutes les équipes</a>
            @foreach ($teams as $t)
                <a href="{{ route('admin.schedules.index', ['team' => $t->id]) }}" class="btn btn-sm {{ $selectedTeam == $t->id ? 'btn-primary' : 'btn-soft' }}">{{ $t->name }}</a>
            @endforeach
        </div>

        @foreach (\App\Models\CollectionSchedule::DAYS as $num => $day)
            @php($items = $grid->get($num, collect()))
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span>{{ $day }}</span>
                    <span class="small text-muted fw-normal">{{ $items->count() }} passage(s)</span>
                </div>
                @if ($items->isEmpty())
                    <div class="card-body small text-muted py-3">Pas de collecte programmée.</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 small">
                            <tbody>
                                @foreach ($items as $schedule)
                                    <tr class="{{ $schedule->is_active ? '' : 'opacity-50' }}">
                                        <td class="fw-bold tabular text-nowrap" style="width: 120px">{{ $schedule->timeRange() }}</td>
                                        <td class="fw-semibold">{{ $schedule->quartier->name }}</td>
                                        <td><i class="bi {{ $schedule->waste_type->icon() }}"></i> {{ $schedule->waste_type->label() }}</td>
                                        <td><span class="d-inline-block rounded-circle me-1" style="width:9px;height:9px;background:{{ $schedule->team?->color }}"></span>{{ $schedule->team?->name ?? 'Sans équipe' }}</td>
                                        <td class="text-end text-nowrap">
                                            <form method="POST" action="{{ route('admin.schedules.update', $schedule) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="toggle" value="1">
                                                <button class="btn btn-soft btn-sm" title="{{ $schedule->is_active ? 'Suspendre' : 'Réactiver' }}">
                                                    <i class="bi {{ $schedule->is_active ? 'bi-pause' : 'bi-play' }}"></i>
                                                    <span class="visually-hidden">{{ $schedule->is_active ? 'Suspendre' : 'Réactiver' }}</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" class="d-inline" onsubmit="return confirm('Supprimer ce créneau ? Les passages futurs non effectués seront retirés.')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-soft btn-sm text-danger" title="Supprimer"><i class="bi bi-trash"></i><span class="visually-hidden">Supprimer</span></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="col-xl-4">
        <div class="card position-sticky" style="top: 80px">
            <div class="card-header"><i class="bi bi-plus-circle me-1"></i> Programmer un passage régulier</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.schedules.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12">
                        <label class="form-label small" for="quartier_id">Quartier</label>
                        <select id="quartier_id" name="quartier_id" class="form-select" required>
                            @foreach ($quartiers as $q)
                                <option value="{{ $q->id }}" @selected(old('quartier_id') == $q->id)>{{ $q->name }} ({{ $q->ville }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="day_of_week">Jour</label>
                        <select id="day_of_week" name="day_of_week" class="form-select">
                            @foreach (\App\Models\CollectionSchedule::DAYS as $num => $day)
                                <option value="{{ $num }}" @selected(old('day_of_week') == $num)>{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="team_id">Équipe</label>
                        <select id="team_id" name="team_id" class="form-select">
                            @foreach ($teams as $t)
                                <option value="{{ $t->id }}" @selected(old('team_id', $selectedTeam) == $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="start_time">Début</label>
                        <input id="start_time" type="time" name="start_time" value="{{ old('start_time', '07:00') }}" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="end_time">Fin</label>
                        <input id="end_time" type="time" name="end_time" value="{{ old('end_time', '08:00') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small" for="waste_type">Type de collecte</label>
                        <select id="waste_type" name="waste_type" class="form-select">
                            @foreach ($wasteTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100 mt-1">Ajouter au calendrier</button>
                        <p class="small text-muted mt-2 mb-0">Les passages des 14 prochains jours sont générés automatiquement et visibles par les habitants.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
