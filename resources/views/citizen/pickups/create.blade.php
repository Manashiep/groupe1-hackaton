@extends('layouts.app')

@section('title', 'Demander une collecte')

@include('partials.leaflet')

@section('content')
<div class="page-head">
    <a href="{{ route('citizen.pickups.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Mes demandes</a>
    <h1 class="mt-2">Demander une collecte exceptionnelle</h1>
    <p class="text-muted mb-0">Particuliers, restaurants, commerces : décrivez vos déchets, le service vous propose un rendez-vous.</p>
</div>

<form method="POST" action="{{ route('citizen.pickups.store') }}" novalidate>
    @csrf
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="requester_type" class="form-label">Vous êtes</label>
                            <select id="requester_type" name="requester_type" class="form-select">
                                @foreach (\App\Models\PickupRequest::REQUESTER_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('requester_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="waste_type" class="form-label">Type de déchets</label>
                            <select id="waste_type" name="waste_type" class="form-select">
                                @foreach (\App\Enums\WasteType::cases() as $type)
                                    <option value="{{ $type->value }}" @selected(old('waste_type', 'encombrants') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <span class="form-label d-block">Quantité estimée</span>
                            <div class="btn-group w-100 flex-wrap" role="group" aria-label="Quantité">
                                @foreach (\App\Models\PickupRequest::VOLUMES as $value => $label)
                                    <input type="radio" class="btn-check" name="volume" id="vol-{{ $value }}" value="{{ $value }}" @checked(old('volume', 'moyen') === $value)>
                                    <label class="btn btn-outline-primary btn-sm" for="vol-{{ $value }}">{{ $label }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Ex. : un canapé, deux matelas et une armoire après un déménagement.">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="preferred_date" class="form-label">Date souhaitée <span class="text-muted fw-normal">(facultatif)</span></label>
                            <input type="date" id="preferred_date" name="preferred_date" value="{{ old('preferred_date') }}" min="{{ today()->toDateString() }}" class="form-control @error('preferred_date') is-invalid @enderror">
                            @error('preferred_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="quartier_id" class="form-label">Quartier</label>
                            <select id="quartier_id" name="quartier_id" class="form-select">
                                @foreach ($quartiers as $q)
                                    <option value="{{ $q->id }}" @selected(old('quartier_id', $defaultQuartier?->id) == $q->id)>{{ $q->name }} ({{ $q->ville }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Adresse ou repère</label>
                            <input id="address" name="address" value="{{ old('address') }}" class="form-control @error('address') is-invalid @enderror" placeholder="Ex. : maison bleue en face de l'école">
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0">Position <span class="text-muted fw-normal small">(facultatif, aide l'équipe)</span></h2>
                        <button type="button" id="locate" class="btn btn-soft btn-sm"><i class="bi bi-crosshair"></i> Ma position</button>
                    </div>
                    <p class="small text-muted mt-2 mb-2" id="loc-status">Touchez la carte pour indiquer l'endroit exact.</p>
                    <div id="map" class="map" style="height: 320px"></div>
                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                </div>
            </div>
            <button class="btn btn-primary btn-lg w-100 mt-4"><i class="bi bi-send-fill me-1"></i> Envoyer la demande</button>
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
});
</script>
@endpush
