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
