@extends('layouts.admin')

@section('title', $team->exists ? 'Modifier '.$team->name : 'Nouvelle équipe')

@section('content')
<div class="row">
    <div class="col-lg-7">
        <form method="POST" action="{{ $team->exists ? route('admin.teams.update', $team) : route('admin.teams.store') }}" class="card">
            @csrf
            @if ($team->exists) @method('PUT') @endif
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Nom de l'équipe</label>
                    <input id="name" name="name" value="{{ old('name', $team->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="vehicle_plate">Immatriculation</label>
                    <input id="vehicle_plate" name="vehicle_plate" value="{{ old('vehicle_plate', $team->vehicle_plate) }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="color">Couleur</label>
                    <input id="color" type="color" name="color" value="{{ old('color', $team->color) }}" class="form-control form-control-color w-100">
                </div>
                <div class="col-12">
                    <span class="form-label d-block">Agents de l'équipe</span>
                    <div class="row g-2">
                        @php($current = old('agents', $team->exists ? $team->agents->pluck('id')->all() : []))
                        @forelse ($agents as $agent)
                            <div class="col-md-6">
                                <div class="form-check border rounded-3 p-2 ps-5">
                                    <input class="form-check-input" type="checkbox" name="agents[]" value="{{ $agent->id }}" id="agent-{{ $agent->id }}" @checked(in_array($agent->id, $current))>
                                    <label class="form-check-label small" for="agent-{{ $agent->id }}">
                                        <strong>{{ $agent->name }}</strong>
                                        <span class="d-block text-muted">{{ $agent->team && $agent->team_id !== $team->id ? 'Actuellement : '.$agent->team->name : ($agent->team ? 'Dans cette équipe' : 'Sans équipe') }}</span>
                                    </label>
                                </div>
                            </div>
                        @empty
                            <p class="small text-muted">Aucun compte agent. Créez-en depuis « Comptes utilisateurs ».</p>
                        @endforelse
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('admin.teams.index') }}" class="btn btn-soft">Annuler</a>
                </div>
            </div>
        </form>
        @if ($team->exists)
            <form method="POST" action="{{ route('admin.teams.destroy', $team) }}" class="mt-3" onsubmit="return confirm('Supprimer cette équipe ?')">
                @csrf @method('DELETE')
                <button class="btn btn-link text-danger p-0 small">Supprimer l'équipe</button>
            </form>
        @endif
    </div>
</div>
@endsection
