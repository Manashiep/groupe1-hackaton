@extends('layouts.admin')

@section('title', 'Demandes de collecte exceptionnelle')

@section('content')
<ul class="nav nav-pills gap-1 mb-3 flex-nowrap overflow-auto pb-1">
    @foreach ([...array_map(fn ($s) => [$s->value, $s->label()], \App\Enums\PickupStatus::cases()), ['toutes', 'Toutes']] as [$value, $label])
        <li class="nav-item">
            <a href="{{ route('admin.pickups.index', ['status' => $value]) }}" class="nav-link text-nowrap {{ $status === $value ? 'active' : '' }}">
                {{ $label }}
                @if ($value !== 'toutes')<span class="badge rounded-pill {{ $status === $value ? 'bg-white text-primary' : 'text-bg-light' }} ms-1">{{ $counts[$value] ?? 0 }}</span>@endif
            </a>
        </li>
    @endforeach
</ul>

<div class="row g-3">
    @forelse ($pickups as $pickup)
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="small text-muted">{{ $pickup->reference() }} · reçue {{ $pickup->created_at->diffForHumans() }}</div>
                            <h2 class="h6 mt-1 mb-0">{{ $pickup->requesterLabel() }} · {{ $pickup->quartier?->name }}</h2>
                        </div>
                        <x-status :status="$pickup->status" />
                    </div>
                    <div class="d-flex flex-wrap gap-2 my-2">
                        <span class="status bg-light border text-body"><i class="bi {{ $pickup->waste_type->icon() }}"></i> {{ $pickup->waste_type->label() }}</span>
                        <span class="status bg-light border text-body"><i class="bi bi-box"></i> {{ $pickup->volumeLabel() }}</span>
                        @if ($pickup->preferred_date)<span class="status bg-light border text-body"><i class="bi bi-calendar"></i> souhaitée le {{ $pickup->preferred_date->translatedFormat('D j M') }}</span>@endif
                    </div>
                    <p class="small mb-1">{{ $pickup->description }}</p>
                    <div class="small text-muted"><i class="bi bi-geo-alt"></i> {{ $pickup->address }} · {{ $pickup->user->name }} @if($pickup->user->phone) · <a href="tel:{{ $pickup->user->phone }}">{{ $pickup->user->phone }}</a>@endif</div>

                    @if ($pickup->status === \App\Enums\PickupStatus::EnAttente)
                        <hr>
                        <form method="POST" action="{{ route('admin.pickups.accept', $pickup) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-sm-5">
                                <label class="form-label small">Rendez-vous</label>
                                <input type="datetime-local" name="appointment_at" class="form-control form-control-sm" value="{{ ($pickup->preferred_date && $pickup->preferred_date->isFuture() ? $pickup->preferred_date->copy() : today()->addDay())->setTime(10, 0)->format('Y-m-d\TH:i') }}" required>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label small">Équipe</label>
                                <select name="team_id" class="form-select form-select-sm">
                                    @foreach ($teams as $t)
                                        <option value="{{ $t->id }}" @selected(($teamByQuartier[$pickup->quartier_id] ?? null) === $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-3"><button class="btn btn-primary btn-sm w-100">Accepter</button></div>
                            <div class="col-12">
                                <input name="admin_note" class="form-control form-control-sm" placeholder="Message à l'habitant (facultatif) : ex. sortir les objets devant le portail">
                            </div>
                        </form>
                        <button class="btn btn-link btn-sm text-danger p-0 mt-2" data-bs-toggle="collapse" data-bs-target="#refuse-{{ $pickup->id }}">Refuser la demande…</button>
                        <form method="POST" action="{{ route('admin.pickups.refuse', $pickup) }}" class="collapse mt-2" id="refuse-{{ $pickup->id }}">
                            @csrf
                            <div class="d-flex gap-2">
                                <input name="admin_note" class="form-control form-control-sm" placeholder="Motif communiqué au demandeur" required>
                                <button class="btn btn-danger btn-sm">Refuser</button>
                            </div>
                        </form>
                    @elseif ($pickup->appointment_at)
                        <div class="alert alert-info small mt-3 mb-0 py-2">
                            <i class="bi bi-calendar-check"></i> RDV {{ $pickup->appointment_at->translatedFormat('l j F à H\hi') }} · {{ $pickup->intervention?->team?->name }}
                            @if ($pickup->intervention)<x-status :status="$pickup->intervention->status" class="ms-1" />@endif
                        </div>
                    @elseif ($pickup->admin_note)
                        <div class="alert alert-secondary small mt-3 mb-0 py-2">{{ $pickup->admin_note }}</div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="empty"><i class="bi bi-inbox"></i>Aucune demande dans cette catégorie.</div></div></div>
    @endforelse
</div>
<div class="mt-3">{{ $pickups->links() }}</div>
@endsection
