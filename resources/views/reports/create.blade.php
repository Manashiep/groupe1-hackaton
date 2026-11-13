@extends('layouts.app')

@section('title', 'Signaler')

@include('partials.leaflet')

@section('content')
<div class="hero">
    <div class="inner">
        <a href="{{ route('map') }}" class="btn btn-ghost btn-sm mb-3"><i class="bi bi-arrow-left"></i> Carte</a>
        <h1>Signaler un dépôt sauvage</h1>
        <p>Il apparaîtra sur la carte : un voisin ou une association pourra s'en occuper.</p>
    </div>
</div>
<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" id="report-form" novalidate>
            @csrf
            <label for="photo" class="d-block mb-3" style="cursor:pointer">
                <span class="panel d-flex align-items-center gap-3" id="photo-placeholder">
                    <span class="icon-chip ico-red" style="width:56px;height:56px;font-size:1.5rem"><i class="bi bi-camera-fill"></i></span>
                    <span><strong class="d-block">Prendre une photo</strong><span class="text-muted">ou en choisir une dans la galerie</span></span>
                </span>
                <img id="photo-preview" alt="Aperçu de la photo" class="d-none w-100" style="max-height:280px;object-fit:cover;border-radius:var(--radius)">
            </label>
            <input type="file" name="photo" id="photo" accept="image/*" class="visually-hidden">

            <div class="form-label">L'endroit</div>
            <div id="map" class="map mb-2"></div>
            <p class="text-muted small mb-3" id="loc-status"></p>
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

            <label for="description" class="form-label">Quelques mots <span class="text-muted fw-normal">(facultatif)</span></label>
            <textarea id="description" name="description" rows="2" maxlength="500" class="form-control mb-4" placeholder="Ex. : gros tas d'ordures derrière l'école">{{ old('description') }}</textarea>

            <button class="btn btn-primary btn-lg w-100" id="submit-btn"><i class="bi bi-send-fill me-2"></i>Envoyer</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    CC.locationPicker({ map: 'map', lat: 'latitude', lng: 'longitude', status: 'loc-status' });

    CC.compressPhotoInput(document.getElementById('photo'), (url) => {
        const preview = document.getElementById('photo-preview');
        preview.src = url;
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
