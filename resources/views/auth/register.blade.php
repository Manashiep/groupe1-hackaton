@extends('layouts.app')

@section('title', 'Créer un compte')

@section('content')
<div class="row justify-content-center py-4 py-lg-5">
    <div class="col-md-9 col-lg-6">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3">Créer mon compte citoyen</h1>
                <p class="text-muted">Recevez les rappels de collecte de votre quartier, signalez les dépôts sauvages et suivez leur nettoyage.</p>
                <form method="POST" action="{{ route('register') }}" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Nom complet</label>
                            <input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autocomplete="name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Adresse e-mail</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="email">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Téléphone <span class="text-muted fw-normal">(facultatif)</span></label>
                            <input id="phone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="+269 …" autocomplete="tel">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="quartier_id" class="form-label">Mon quartier / village</label>
                            <select id="quartier_id" name="quartier_id" class="form-select @error('quartier_id') is-invalid @enderror" required>
                                <option value="">Choisir…</option>
                                @foreach ($quartiers->groupBy('ville') as $ville => $items)
                                    <optgroup label="{{ $ville }}">
                                        @foreach ($items as $q)
                                            <option value="{{ $q->id }}" @selected(old('quartier_id') == $q->id)>{{ $q->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="form-text">Il sert à vous envoyer les rappels de passage de la camionnette.</div>
                            @error('quartier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                            <div class="form-text">8 caractères minimum.</div>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirmation</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                        </div>
                    </div>
                    <button class="btn btn-primary btn-lg w-100 mt-4">Créer mon compte</button>
                </form>
                <p class="text-center small text-muted mt-4 mb-0">Déjà inscrit ? <a href="{{ route('login') }}" class="fw-semibold">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
