@extends('layouts.app')

@section('title', 'Demander une collecte')

@include('partials.leaflet')

@section('content')
<div class="hero">
    <div class="inner">
        <a href="{{ route('map') }}" class="btn btn-ghost btn-sm mb-3"><i class="bi bi-arrow-left"></i> Carte</a>
        <h1>Demander une collecte</h1>
        <p>Beaucoup de déchets ou des encombrants ? Votre demande apparaît sur la carte et un volontaire ou une association vient les chercher.</p>
    </div>
</div>
<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        <form method="POST" action="{{ route('pickups.store') }}" novalidate>
            @csrf
            <label for="description" class="form-label">Qu'y a-t-il à ramasser ?</label>
            <textarea id="description" name="description" rows="3" maxlength="500" class="form-control mb-3" placeholder="Ex. : un vieux canapé et deux matelas" required>{{ old('description') }}</textarea>

            <div class="form-label">Où ?</div>
            <div id="map" class="map mb-2"></div>
            <p class="text-muted small mb-3" id="loc-status"></p>
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

            <label for="preferred_date" class="form-label">Quel jour vous arrange ? <span class="text-muted fw-normal">(facultatif)</span></label>
            <input type="date" id="preferred_date" name="preferred_date" value="{{ old('preferred_date') }}" min="{{ today()->toDateString() }}" class="form-control mb-4">

            <button class="btn btn-primary btn-lg w-100"><i class="bi bi-send-fill me-2"></i>Publier la demande</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => CC.locationPicker({ map: 'map', lat: 'latitude', lng: 'longitude', status: 'loc-status' }));
</script>
@endpush
