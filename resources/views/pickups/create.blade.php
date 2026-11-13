@extends('layouts.app')

@section('title', 'Demander une collecte')

@include('partials.leaflet')

@section('content')
<div class="page">
    <a href="{{ route('pickups.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Mes demandes</a>
    <h1 class="mt-2 mb-3">Demander une collecte</h1>

    @include('partials.flash')

    <form method="POST" action="{{ route('pickups.store') }}" novalidate>
        @csrf
        <label for="description" class="form-label">Qu'y a-t-il à ramasser ?</label>
        <textarea id="description" name="description" rows="3" maxlength="500" class="form-control mb-3" placeholder="Ex. : un vieux canapé et deux matelas" required>{{ old('description') }}</textarea>

        <label class="form-label">Où ?</label>
        <div id="map" class="map mb-2"></div>
        <p class="small text-muted mb-3" id="loc-status"></p>
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

        <label for="preferred_date" class="form-label">Quel jour vous arrange ? <span class="text-muted fw-normal">(facultatif)</span></label>
        <input type="date" id="preferred_date" name="preferred_date" value="{{ old('preferred_date') }}" min="{{ today()->toDateString() }}" class="form-control mb-4">

        <button class="btn btn-primary btn-lg w-100"><i class="bi bi-send-fill me-2"></i>Envoyer la demande</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => CC.locationPicker({ map: 'map', lat: 'latitude', lng: 'longitude', status: 'loc-status' }));
</script>
@endpush
