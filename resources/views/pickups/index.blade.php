@extends('layouts.app')

@section('title', 'Demandes de collecte')

@section('content')
<div class="page">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h1 class="mb-0">Demandes de collecte</h1>
        <a href="{{ route('pickups.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Demander</a>
    </div>
    <p class="text-muted small mb-3">Beaucoup de déchets ou des objets encombrants ? La mairie vous propose un jour de passage.</p>

    @include('partials.flash')

    @forelse ($pickups as $pickup)
        <div class="item">
            <div class="thumb"><i class="bi bi-box-seam"></i></div>
            <div class="flex-grow-1">
                <span class="pill" style="background: {{ $pickup->status->hex() }}1a; color: {{ $pickup->status->hex() }}">
                    <span class="dot" style="background: {{ $pickup->status->hex() }}"></span>{{ $pickup->status->getLabel() }}
                </span>
                <div class="mt-1">{{ $pickup->description }}</div>
                @if ($pickup->appointment_at && $pickup->status === \App\Enums\PickupStatus::Acceptee)
                    <div class="small fw-bold mt-1"><i class="bi bi-calendar-check"></i> {{ ucfirst($pickup->appointment_at->translatedFormat('l j F à H\hi')) }} · {{ $pickup->truck?->name }}</div>
                @endif
                @if ($pickup->admin_note)<div class="small text-muted mt-1">{{ $pickup->admin_note }}</div>@endif
                <div class="small text-muted">Demandé {{ $pickup->created_at->diffForHumans() }}</div>
            </div>
        </div>
    @empty
        <div class="empty"><i class="bi bi-box-seam"></i>Aucune demande pour l'instant.</div>
    @endforelse
</div>
@endsection
