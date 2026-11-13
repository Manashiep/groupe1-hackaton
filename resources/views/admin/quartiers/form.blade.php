@extends('layouts.admin')

@section('title', $quartier->exists ? 'Modifier '.$quartier->name : 'Nouveau quartier')

@include('partials.leaflet')

@section('content')
<form method="POST" action="{{ $quartier->exists ? route('admin.quartiers.update', $quartier) : route('admin.quartiers.store') }}">
    @csrf
    @if ($quartier->exists) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="name">Nom du quartier / village</label>
                        <input id="name" name="name" value="{{ old('name', $quartier->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ville">Ville / commune</label>
                        <input id="ville" name="ville" value="{{ old('ville', $quartier->ville) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ile">Île</label>
                        <select id="ile" name="ile" class="form-select">
                            @foreach (['Ngazidja', 'Ndzuwani', 'Mwali'] as $ile)
                                <option @selected(old('ile', $quartier->ile) === $ile)>{{ $ile }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="population">Population estimée</label>
                        <input id="population" type="number" min="0" name="population" value="{{ old('population', $quartier->population) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Centre (carte)</label>
                        <div class="small text-muted tabular" id="coords">{{ $quartier->latitude ? number_format($quartier->latitude, 5).', '.number_format($quartier->longitude, 5) : 'Cliquez sur la carte' }}</div>
                    </div>
                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $quartier->latitude) }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $quartier->longitude) }}">
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.quartiers.index') }}" class="btn btn-soft">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div id="map" class="map" style="height: 460px"></div>
            <p class="small text-muted mt-2">Cliquez au centre du quartier : ce point sert au calendrier, aux itinéraires et à la carte publique.</p>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    CC.locationPicker({ map: 'map', lat: 'latitude', lng: 'longitude' });
    const coords = document.getElementById('coords');
    const sync = () => coords.textContent = (+document.getElementById('latitude').value).toFixed(5) + ', ' + (+document.getElementById('longitude').value).toFixed(5);
    document.getElementById('map').addEventListener('click', () => setTimeout(sync, 0));
});
</script>
@endpush
