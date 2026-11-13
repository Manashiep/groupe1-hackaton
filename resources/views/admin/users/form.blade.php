@extends('layouts.admin')

@section('title', $user->exists ? 'Modifier le compte de '.$user->name : 'Créer un compte')

@section('content')
<div class="row">
    <div class="col-lg-7">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card">
            @csrf
            @if ($user->exists) @method('PUT') @endif
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Nom complet</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">E-mail</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Téléphone</label>
                    <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="role">Rôle</label>
                    <select id="role" name="role" class="form-select">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6" id="team-field">
                    <label class="form-label" for="team_id">Équipe (agents)</label>
                    <select id="team_id" name="team_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected(old('team_id', $user->team_id) == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="quartier_id">Quartier</label>
                    <select id="quartier_id" name="quartier_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($quartiers as $q)
                            <option value="{{ $q->id }}" @selected(old('quartier_id', $user->quartier_id) == $q->id)>{{ $q->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password">{{ $user->exists ? 'Nouveau mot de passe' : 'Mot de passe' }}</label>
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                    @if ($user->exists)<div class="form-text">Laisser vide pour ne pas le changer.</div>@endif
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="is_active" @checked(old('is_active', $user->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Compte actif</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-soft">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const role = document.getElementById('role'), field = document.getElementById('team-field');
const toggle = () => field.classList.toggle('d-none', role.value !== 'agent');
role.addEventListener('change', toggle); toggle();
</script>
@endpush
