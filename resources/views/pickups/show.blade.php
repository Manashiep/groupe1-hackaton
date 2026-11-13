@extends('layouts.app')

@section('title', 'Demande de collecte')

@include('partials.leaflet')

@use('App\Enums\PickupStatus')

@section('content')
<div class="hero">
    <div class="inner">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('map') }}" class="btn btn-ghost btn-sm mb-3"><i class="bi bi-arrow-left"></i> Retour</a>
        <span class="pill st-{{ $pickup->status->value }}"><span class="dot"></span>{{ $pickup->status->getLabel() }}</span>
        <h1 class="mt-2">{{ $pickup->description }}</h1>
        <p><i class="bi bi-geo-alt"></i> {{ $pickup->village?->name ?? 'Ngazidja' }} · demandé par {{ $pickup->user->shortName() }} {{ $pickup->created_at->diffForHumans() }}
            @if ($pickup->preferred_date) · idéalement le {{ $pickup->preferred_date->translatedFormat('l j F') }} @endif</p>
    </div>
</div>
<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        @if ($pickup->taken_by_id)
            <div class="panel d-flex gap-3 align-items-center mb-3">
                @if ($pickup->association)
                    <span class="asso-avatar" style="background: {{ $pickup->association->color }}">{{ $pickup->association->initials() }}</span>
                @else
                    <span class="avatar">{{ $pickup->taker?->initials() }}</span>
                @endif
                <div>
                    <div class="fw-bold">
                        {{ $pickup->status === PickupStatus::Effectuee ? 'Ramassé par' : 'Pris en charge par' }}
                        @if ($pickup->association)<a href="{{ route('associations.show', $pickup->association) }}">{{ $pickup->association->name }}</a>@else{{ $pickup->taker?->shortName() }}@endif
                    </div>
                    <div class="row-sub">{{ $pickup->status === PickupStatus::Effectuee ? $pickup->done_at?->translatedFormat('l j F') : $pickup->appointmentLabel() }}</div>
                    @if ($pickup->taker_note)<div class="small mt-1">« {{ $pickup->taker_note }} »</div>@endif
                </div>
            </div>
        @endif

        @if ($pickup->status === PickupStatus::Refusee && $pickup->admin_note)
            <div class="callout mb-3"><i class="bi bi-info-circle-fill"></i><div>{{ $pickup->admin_note }}</div></div>
        @endif

        <div id="map" class="map"></div>

        @include('partials.take-charge', [
            'item' => $pickup,
            'isOpen' => $pickup->status === PickupStatus::EnAttente && $pickup->user_id !== auth()->id(),
            'takeRoute' => route('pickups.take', $pickup),
            'plannedLabel' => $pickup->appointmentLabel(),
            'plannedAt' => $pickup->appointment_at,
            'routes' => [
                'postpone' => route('pickups.postpone', $pickup),
                'finish' => route('pickups.finish', $pickup),
                'release' => route('pickups.release', $pickup),
            ],
        ])

        @if ($pickup->status === PickupStatus::EnAttente && $pickup->user_id === auth()->id())
            <div class="callout callout-info mt-3"><i class="bi bi-hourglass-split"></i><div>Votre demande est visible sur la carte. Vous verrez ici qui vient et quand.</div></div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const at = [{{ $pickup->latitude }}, {{ $pickup->longitude }}];
    const map = CC.baseMap('map', { center: at, zoom: 17 });
    L.marker(at, { icon: CC.pin(@js($pickup->status->hex()), true) }).addTo(map);
});
</script>
@endpush
