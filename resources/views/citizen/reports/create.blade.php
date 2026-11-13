@extends('layouts.app')

@section('title', 'Signaler des déchets')

@include('partials.leaflet')

@section('content')
<div class="page-head">
    <a href="{{ route('citizen.dashboard') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Mon espace</a>
    <h1 class="mt-2">Signaler des déchets abandonnés</h1>
    <p class="text-muted mb-0">Moins d'une minute : une photo, l'emplacement, quelques mots.</p>
</div>

<form method="POST" action="{{ route('citizen.reports.store') }}" enctype="multipart/form-data" novalidate id="report-form">
    @csrf
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6"><span class="step-num d-inline-grid me-2">1</span> La photo</h2>
                    <label for="photo" class="photo-drop mt-3" id="photo-drop">
                        <span id="photo-placeholder">
                            <i class="bi bi-camera fs-1 text-primary d-block"></i>
                            <strong>Prendre ou importer une photo</strong>
                            <span class="d-block small text-muted">JPG ou PNG, 6 Mo maximum. Fortement recommandée : elle accélère la vérification.</span>
                        </span>
                        <img id="photo-preview" alt="Aperçu de la photo" class="d-none img-fluid">
                    </label>
                    <input type="file" name="photo" id="photo" accept="image/*" class="visually-hidden">
                    @error('photo')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h2 class="h6"><span class="step-num d-inline-grid me-2">2</span> Le problème</h2>
                    <fieldset class="mt-3">
                        <legend class="form-label">Type de problème</legend>
                        <div class="row g-2">
                            @foreach ($categories as $category)
                                <div class="col-6 category-option">
                                    <input type="radio" name="category" value="{{ $category->value }}" id="cat-{{ $category->value }}" @checked(old('category', 'depot_sauvage') === $category->value)>
                                    <label for="cat-{{ $category->value }}"><i class="bi {{ $category->icon() }}"></i> {{ $category->label() }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('category')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </fieldset>
                    <div class="mt-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Ex. : tas d'ordures au bord de la route depuis 3 jours, odeurs fortes." required>{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0"><span class="step-num d-inline-grid me-2">3</span> L'emplacement</h2>
                        <button type="button" id="locate" class="btn btn-soft btn-sm"><i class="bi bi-crosshair"></i> Ma position</button>
                    </div>
                    <p class="small text-muted mt-2 mb-2" id="loc-status">Touchez la carte à l'endroit des déchets, ou utilisez votre position.</p>
                    <div id="map" class="map" style="height: 340px"></div>
                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                    @error('latitude')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="quartier_id" class="form-label">Quartier</label>
                            <select id="quartier_id" name="quartier_id" class="form-select @error('quartier_id') is-invalid @enderror" required>
                                @foreach ($quartiers as $q)
                                    <option value="{{ $q->id }}" @selected(old('quartier_id', $defaultQuartier?->id) == $q->id)>{{ $q->name }} ({{ $q->ville }})</option>
                                @endforeach
                            </select>
                            @error('quartier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">Repère <span class="text-muted fw-normal">(facultatif)</span></label>
                            <input id="address" name="address" value="{{ old('address') }}" class="form-control" placeholder="Ex. : derrière la mosquée">
                        </div>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary btn-lg w-100 mt-4" id="submit-btn"><i class="bi bi-send-fill me-1"></i> Envoyer le signalement</button>
            <p class="small text-muted text-center mt-2">Votre nom n'apparaît pas sur la carte publique.</p>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const quartiers = @js($quartiers->map(fn ($q) => ['id' => $q->id, 'lat' => $q->latitude, 'lng' => $q->longitude]));
    const start = quartiers.find((q) => String(q.id) === document.getElementById('quartier_id').value);

    CC.locationPicker({
        map: 'map', lat: 'latitude', lng: 'longitude', quartier: 'quartier_id',
        quartiers, locate: 'locate', status: 'loc-status',
        center: start ? [start.lat, start.lng] : undefined,
    });

    const input = document.getElementById('photo');
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        const preview = document.getElementById('photo-preview');
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
        document.getElementById('photo-placeholder').classList.add('d-none');
    });

    document.getElementById('report-form').addEventListener('submit', (e) => {
        if (!document.getElementById('latitude').value) {
            e.preventDefault();
            const status = document.getElementById('loc-status');
            status.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle"></i> Indiquez l\'emplacement sur la carte avant d\'envoyer.</span>';
            status.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Envoi en cours…';
    });
});
</script>
@endpush
