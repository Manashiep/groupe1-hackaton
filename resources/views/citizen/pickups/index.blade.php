@extends('layouts.app')

@section('title', 'Collecte sur demande')

@section('content')
<div class="page-head d-md-flex justify-content-between align-items-end gap-3">
    <div>
        <h1>Collecte sur demande</h1>
        <p class="text-muted mb-0">Pour les grandes quantités et les encombrants : la mairie vous propose un rendez-vous.</p>
    </div>
    <a href="{{ route('citizen.pickups.create') }}" class="btn btn-primary mt-3 mt-md-0"><i class="bi bi-plus-lg me-1"></i> Nouvelle demande</a>
</div>

<div class="row g-3">
    @forelse ($pickups as $pickup)
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="small text-muted">{{ $pickup->reference() }} · {{ $pickup->requesterLabel() }}</div>
                            <h2 class="h6 mt-1 mb-1"><i class="bi {{ $pickup->waste_type->icon() }} text-primary"></i> {{ $pickup->waste_type->label() }} · {{ $pickup->volumeLabel() }}</h2>
                        </div>
                        <x-status :status="$pickup->status" />
                    </div>
                    <p class="small mb-2">{{ $pickup->description }}</p>
                    <div class="small text-muted"><i class="bi bi-geo-alt"></i> {{ $pickup->address }} @if($pickup->preferred_date) · souhaitée le {{ $pickup->preferred_date->translatedFormat('j F') }} @endif</div>

                    @if ($pickup->status === \App\Enums\PickupStatus::Acceptee && $pickup->appointment_at)
                        <div class="alert alert-info small mt-3 mb-0">
                            <strong><i class="bi bi-calendar-check"></i> Rendez-vous : {{ $pickup->appointment_at->translatedFormat('l j F à H\hi') }}</strong>
                            @if ($pickup->intervention?->team) · {{ $pickup->intervention->team->name }} @endif
                            @if ($pickup->admin_note)<div class="mt-1">{{ $pickup->admin_note }}</div>@endif
                        </div>
                    @elseif ($pickup->status === \App\Enums\PickupStatus::Refusee && $pickup->admin_note)
                        <div class="alert alert-secondary small mt-3 mb-0"><strong>Motif :</strong> {{ $pickup->admin_note }}</div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="empty"><i class="bi bi-box-seam"></i>Aucune demande pour l'instant.</div></div></div>
    @endforelse
</div>
<div class="mt-3">{{ $pickups->links() }}</div>
@endsection
