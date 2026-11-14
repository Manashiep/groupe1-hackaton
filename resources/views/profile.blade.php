@extends('layouts.app')

@section('title', 'Moi')

@use('App\Enums\AssociationStatus')
@use('App\Enums\MembershipStatus')

@section('content')
<div class="hero">
    <div class="inner d-flex align-items-center gap-3">
        <span class="avatar avatar-lg">{{ $user->initials() }}</span>
        <div class="flex-grow-1">
            <h1>{{ $user->name }}</h1>
            <p class="mb-0">{{ $user->isAgent() ? 'Agent · '.$user->truck?->name : 'Citoyen engagé' }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-ghost btn-sm" aria-label="Se déconnecter"><i class="bi bi-box-arrow-right"></i></button>
        </form>
    </div>
</div>

<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        @foreach ($ledAssociations as $association)
            @if ($association->status === AssociationStatus::Active)
                <a href="{{ route('associations.manage.show', $association) }}" class="asso-card mb-3" style="--asso: {{ $association->color }}">
                    <span class="asso-avatar" style="background: {{ $association->color }}">{{ $association->initials() }}</span>
                    <span class="flex-grow-1"><strong class="d-block">Gérer {{ $association->name }}</strong><span class="row-sub">Adhésions, annonces et publications</span></span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            @else
                <div class="callout mb-3">
                    <i class="bi bi-hourglass-split"></i>
                    <div><strong>{{ $association->name }}</strong> · {{ $association->status->getLabel() }}
                        @if ($association->admin_note)<div class="small mt-1">{{ $association->admin_note }}</div>@endif
                    </div>
                </div>
            @endif
        @endforeach

        @if ($user->isCitizen())
            <div class="section-title"><h2>Ma redevance de collecte</h2></div>
            <form method="POST" action="{{ route('profile.home-village') }}" class="panel mb-2">
                @csrf @method('PATCH')
                <label for="home_village_id" class="form-label">Mon village de résidence</label>
                <div class="d-flex gap-2">
                    <select id="home_village_id" name="home_village_id" class="form-select">
                        <option value="">Choisir…</option>
                        @foreach ($villages as $village)
                            <option value="{{ $village->id }}" @selected($user->home_village_id === $village->id)>{{ $village->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-soft">Enregistrer</button>
                </div>
                <div class="field-hint">{{ config('cleancomores.due_amount') }} {{ config('cleancomores.currency') }} à chaque passage du camion dans ce village.</div>
            </form>
            <div class="panel py-1">
                @forelse ($dues as $due)
                    <div class="row-item">
                        <span class="icon-chip {{ $due->status === \App\Enums\DueStatus::Payee ? 'ico-leaf' : 'ico-red' }}"><i class="bi bi-receipt"></i></span>
                        <span class="flex-grow-1">
                            <span class="row-title d-block">{{ $due->amountLabel() }} · {{ $due->passage->village->name }}</span>
                            <span class="row-sub">Passage du {{ $due->passage->passed_on->translatedFormat('l j F') }} · {{ $due->passage->truck->name }}</span>
                        </span>
                        @if ($due->isPaid())
                            <a href="{{ route('dues.receipt', $due) }}" class="btn btn-soft btn-sm"><i class="bi bi-qr-code"></i> Preuve</a>
                        @else
                            <form method="POST" action="{{ route('dues.pay', $due) }}">
                                @csrf
                                <button class="btn btn-primary btn-sm">Payer</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="empty py-3"><strong>Aucune redevance</strong>Elle apparaîtra ici quand le camion passera dans votre village.</div>
                @endforelse
            </div>
        @endif

        <div class="section-title"><h2>Mes engagements</h2></div>
        <div class="panel py-1">
            @forelse ($engagedReports as $report)
                <a href="{{ route('reports.show', $report) }}" class="row-item">
                    <span class="icon-chip st-{{ $report->status->value }}"><i class="bi bi-trash3-fill"></i></span>
                    <span class="flex-grow-1"><span class="row-title d-block">{{ $report->description ?: 'Dépôt sauvage' }}</span><span class="row-sub">{{ $report->cleanupLabel() }}</span></span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            @empty
                @if ($engagedPickups->isEmpty())
                    <div class="empty py-3">
                        <strong>Vous ne vous êtes engagé sur rien pour l'instant</strong>
                        Ouvrez la carte et touchez un point rouge pour vous en occuper.
                    </div>
                @endif
            @endforelse
            @foreach ($engagedPickups as $pickup)
                <a href="{{ route('pickups.show', $pickup) }}" class="row-item">
                    <span class="icon-chip st-{{ $pickup->status->value }}"><i class="bi bi-box-seam-fill"></i></span>
                    <span class="flex-grow-1"><span class="row-title d-block">{{ $pickup->description }}</span><span class="row-sub">{{ $pickup->appointmentLabel() }}</span></span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            @endforeach
        </div>

        <div class="section-title"><h2>Mes signalements</h2><a href="{{ route('reports.create') }}">+ Signaler</a></div>
        <div class="panel py-1">
            @forelse ($reports as $report)
                <a href="{{ route('reports.show', $report) }}" class="row-item">
                    <span class="thumb">@if ($report->photoUrl())<img src="{{ $report->photoUrl() }}" alt="">@else<i class="bi bi-trash3"></i>@endif</span>
                    <span class="flex-grow-1" style="min-width:0">
                        <span class="row-title d-block text-truncate">{{ $report->description ?: 'Dépôt sauvage' }}</span>
                        <span class="pill st-{{ $report->status->value }} mt-1"><span class="dot"></span>{{ $report->status->getLabel() }}</span>
                        @if ($report->takerName() && $report->cleanup_at)<span class="row-sub d-block mt-1">{{ $report->takerName() }} · {{ $report->cleanup_at->translatedFormat('D j M, H\hi') }}</span>@endif
                    </span>
                </a>
            @empty
                <div class="empty py-3"><strong>Aucun signalement</strong>Le bouton jaune « + » permet d'en faire un en 20 secondes.</div>
            @endforelse
        </div>

        <div class="section-title"><h2>Mes demandes de collecte</h2><a href="{{ route('pickups.create') }}">+ Demander</a></div>
        <div class="panel py-1">
            @forelse ($pickups as $pickup)
                <a href="{{ route('pickups.show', $pickup) }}" class="row-item">
                    <span class="thumb"><i class="bi bi-box-seam"></i></span>
                    <span class="flex-grow-1" style="min-width:0">
                        <span class="row-title d-block text-truncate">{{ $pickup->description }}</span>
                        <span class="pill st-{{ $pickup->status->value }} mt-1"><span class="dot"></span>{{ $pickup->status->getLabel() }}</span>
                        @if ($pickup->takerName() && $pickup->appointment_at)<span class="row-sub d-block mt-1">{{ $pickup->takerName() }} · {{ $pickup->appointment_at->translatedFormat('D j M, H\hi') }}</span>@endif
                    </span>
                </a>
            @empty
                <div class="empty py-3"><strong>Aucune demande</strong>Des encombrants à évacuer ? Un volontaire peut passer.</div>
            @endforelse
        </div>

        <div class="section-title"><h2>Mes associations</h2><a href="{{ route('associations.index', ['onglet' => 'annuaire']) }}">Découvrir</a></div>
        <div class="panel py-1">
            @forelse ($memberships->filter(fn ($m) => $m->association->status === AssociationStatus::Active) as $membership)
                <a href="{{ route('associations.show', $membership->association) }}" class="row-item">
                    <span class="asso-avatar" style="background: {{ $membership->association->color }}">{{ $membership->association->initials() }}</span>
                    <span class="flex-grow-1"><span class="row-title d-block">{{ $membership->association->name }}</span>
                        <span class="row-sub">{{ $membership->association->isLedBy($user) ? 'Chef du groupe' : ($membership->status === MembershipStatus::Membre ? 'Membre' : 'Demande envoyée') }}</span></span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            @empty
                <div class="empty py-3"><strong>Pas encore d'association</strong>Rejoignez un groupe de nettoyeurs près de chez vous.</div>
            @endforelse
        </div>
        @if ($ledAssociations->isEmpty())
            <a href="{{ route('associations.create') }}" class="btn btn-soft w-100 mt-3"><i class="bi bi-plus-circle me-1"></i> Créer une association</a>
        @endif
    </div>
</div>
@endsection
