@extends('layouts.app')

@section('title', 'Mon espace')

@section('content')
<div class="page-head d-md-flex justify-content-between align-items-end gap-3">
    <div>
        <div class="small-caps">Espace citoyen</div>
        <h1>Bonjour {{ explode(' ', $user->name)[0] }}</h1>
        <form method="POST" action="{{ route('citizen.quartier.update') }}" class="d-flex align-items-center gap-2 small text-muted">
            @csrf @method('PATCH')
            <label for="my-quartier" class="text-nowrap"><i class="bi bi-geo-alt"></i> Mon quartier :</label>
            <select id="my-quartier" name="quartier_id" class="form-select form-select-sm w-auto py-1" onchange="this.form.submit()">
                @foreach ($quartiers as $q)
                    <option value="{{ $q->id }}" @selected($quartier?->id === $q->id)>{{ $q->name }} ({{ $q->ville }})</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('citizen.pickups.create') }}" class="btn btn-soft"><i class="bi bi-box-seam me-1"></i> Collecte sur demande</a>
        <a href="{{ route('citizen.reports.create') }}" class="btn btn-sun"><i class="bi bi-camera-fill me-1"></i> Signaler des déchets</a>
    </div>
</div>

@if ($quartier)
<div class="row g-4">
    <div class="col-lg-6 col-xl-5">
        @include('partials.next-collection', ['next' => $next, 'quartier' => $quartier])
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span>Collectes de la semaine</span>
                <a href="{{ route('calendar', ['quartier' => $quartier->slug]) }}" class="small fw-semibold text-decoration-none">Calendrier</a>
            </div>
            <div style="max-height: 360px; overflow-y: auto">
                @include('partials.collection-list', ['collections' => $collections, 'complainedIds' => $complainedIds])
            </div>
        </div>
    </div>
    <div class="col-xl-3 d-flex flex-column flex-md-row flex-xl-column gap-4">
        <div class="card flex-fill">
            <div class="card-body">
                <div class="small-caps">Mes points citoyens</div>
                <div class="d-flex align-items-baseline gap-2 mt-1">
                    <span class="display-6 fw-bold tabular">{{ $user->points }}</span><span class="text-muted">pts</span>
                </div>
                <div class="small text-muted">{{ $rank }}<sup>{{ $rank === 1 ? 'er' : 'e' }}</sup> citoyen le plus engagé · +10 pts par signalement vérifié</div>
            </div>
        </div>
        <a href="{{ route('quartiers.show', $quartier) }}" class="card text-decoration-none text-body flex-fill">
            <div class="card-body d-flex align-items-center gap-3">
                <x-score :data="$index" size="ring" />
                <div>
                    <div class="small-caps">Propreté de {{ $quartier->name }}</div>
                    <div class="fw-bold">{{ $index['label'] }}</div>
                    <div class="small text-muted">{{ $index['open'] }} dépôt(s) vérifié(s) en attente</div>
                </div>
            </div>
        </a>
    </div>
</div>
@else
    <div class="alert alert-warning">Choisissez votre quartier ci-dessus pour voir votre calendrier de collecte.</div>
@endif

<div class="row g-4 mt-0">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span>Mes signalements</span>
                <a href="{{ route('citizen.reports.index') }}" class="small fw-semibold text-decoration-none">Tout voir</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($reports as $report)
                    <a href="{{ route('citizen.reports.show', $report) }}" class="list-group-item list-group-item-action d-flex gap-3 align-items-center py-3">
                        <div class="report-thumb">
                            @if ($report->photoUrl())<img src="{{ $report->photoUrl() }}" alt="">@else<i class="bi {{ $report->category->icon() }}"></i>@endif
                        </div>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="fw-semibold">{{ $report->category->label() }} <span class="text-muted small fw-normal">· {{ $report->reference() }}</span></div>
                            <div class="small text-muted text-truncate">{{ $report->address ?? $report->quartier?->name }} · {{ $report->created_at->diffForHumans() }}</div>
                        </div>
                        <x-status :status="$report->status" />
                    </a>
                @empty
                    <div class="empty">
                        <i class="bi bi-camera"></i>
                        Vous n'avez encore rien signalé.<br>
                        <a href="{{ route('citizen.reports.create') }}" class="btn btn-primary btn-sm mt-3">Faire mon premier signalement</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span>Collecte sur demande</span>
                <a href="{{ route('citizen.pickups.index') }}" class="small fw-semibold text-decoration-none">Mes demandes</a>
            </div>
            <div class="card-body">
                <p class="small text-muted">Déménagement, encombrants, fête, restaurant ou commerce : demandez un passage exceptionnel, la mairie vous propose un rendez-vous.</p>
                @foreach ($pickups as $pickup)
                    <div class="d-flex justify-content-between align-items-center border-top py-2 small">
                        <span>
                            <strong>{{ $pickup->waste_type->label() }}</strong><br>
                            <span class="text-muted">
                                @if ($pickup->appointment_at && $pickup->status === \App\Enums\PickupStatus::Acceptee)
                                    RDV {{ $pickup->appointment_at->translatedFormat('l j F à H\hi') }}
                                @else
                                    Demandé {{ $pickup->created_at->diffForHumans() }}
                                @endif
                            </span>
                        </span>
                        <x-status :status="$pickup->status" />
                    </div>
                @endforeach
                <a href="{{ route('citizen.pickups.create') }}" class="btn btn-soft w-100 mt-2"><i class="bi bi-plus-lg me-1"></i> Nouvelle demande</a>
            </div>
        </div>
    </div>
</div>
@endsection
