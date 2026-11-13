@extends('layouts.app')

@section('title', 'Signaler')

@include('partials.leaflet')

@section('content')
<div class="page">
    <a href="{{ route('map') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Carte</a>
    <h1 class="mt-2 mb-3">Signaler un dépôt sauvage</h1>

    @include('partials.flash')

    <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" id="report-form" novalidate>
        @csrf
        <label for="photo" class="photo-drop mb-3">
            <span id="photo-placeholder">
                <i class="bi bi-camera fs-1 text-primary d-block"></i>
                <strong>Prendre une photo</strong>
                <span class="d-block small text-muted">ou en choisir une dans la galerie</span>
            </span>
            <img id="photo-preview" alt="Aperçu" class="d-none">
        </label>
        <input type="file" name="photo" id="photo" accept="image/*" class="visually-hidden">

        <div id="map" class="map mb-2"></div>
        <p class="small text-muted mb-3" id="loc-status"></p>
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

        <label for="description" class="form-label">Quelques mots <span class="text-muted fw-normal">(facultatif)</span></label>
        <textarea id="description" name="description" rows="2" maxlength="500" class="form-control mb-4" placeholder="Ex. : gros tas d'ordures derrière l'école">{{ old('description') }}</textarea>

        <button class="btn btn-primary btn-lg w-100" id="submit-btn"><i class="bi bi-send-fill me-2"></i>Envoyer</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    CC.locationPicker({ map: 'map', lat: 'latitude', lng: 'longitude', status: 'loc-status' });

    const input = document.getElementById('photo');
    input.addEventListener('change', () => {
        if (!input.files[0]) return;
        const preview = document.getElementById('photo-preview');
        preview.src = URL.createObjectURL(input.files[0]);
        preview.classList.remove('d-none');
        document.getElementById('photo-placeholder').classList.add('d-none');
    });

    document.getElementById('report-form').addEventListener('submit', (e) => {
        if (!document.getElementById('latitude').value) {
            e.preventDefault();
            document.getElementById('loc-status').innerHTML = '<span class="text-danger fw-bold">Touchez la carte pour indiquer l\'endroit.</span>';
            return;
        }
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi…';
    });
});
</script>
@endpush
