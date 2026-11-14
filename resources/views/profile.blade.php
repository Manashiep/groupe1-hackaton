@extends('layouts.app')

@section('title', 'Moi')

@use('App\Enums\AssociationStatus')
@use('App\Enums\DueStatus')
@use('App\Enums\MembershipStatus')

@section('content')
{{-- En-tête : qui je suis, puis le résumé en 3 chiffres avant le détail. --}}
<div class="hero">
    <div class="inner wide">
        <div class="d-flex align-items-center gap-3">
            <span class="avatar avatar-lg">{{ $user->initials() }}</span>
            <div class="flex-grow-1">
                <h1>{{ $user->name }}</h1>
                <p class="mb-0">{{ $user->isAgent() ? 'Agent · '.$user->truck?->name : 'Citoyen engagé' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost btn-sm" aria-label="Se déconnecter"><i class="bi bi-box-arrow-right"></i><span class="d-none d-md-inline ms-1">Déconnexion</span></button>
            </form>
        </div>
        <div class="stat-tiles">
            <div class="stat-tile">
                <b>{{ $myRanking['points'] ?? 0 }}</b>
                <span>points ce mois{{ $myRanking ? ' · '.($myRanking['rank'] === 1 ? '1er' : $myRanking['rank'].'e') : '' }}</span>
            </div>
            <div class="stat-tile">
                <b>{{ $reports->count() }}</b>
                <span>signalement{{ $reports->count() > 1 ? 's' : '' }}</span>
            </div>
            <div class="stat-tile">
                <b>{{ $engagedReports->count() + $engagedPickups->count() }}</b>
                <span>engagement{{ $engagedReports->count() + $engagedPickups->count() > 1 ? 's' : '' }} en cours</span>
            </div>
        </div>
    </div>
</div>

<div class="sheet">
    <div class="inner wide">
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

        <div class="profile-grid">
            {{-- Colonne principale : ce que je fais (engagements, signalements, demandes). --}}
            <div class="profile-col">
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
                        @if ($loop->index === 4 && $loop->count > 5)
                            <div class="collapse" id="more-reports">
                        @endif
                        <a href="{{ route('reports.show', $report) }}" class="row-item">
                            <span class="thumb">@if ($report->photoUrl())<img src="{{ $report->photoUrl() }}" alt="">@else<i class="bi bi-trash3"></i>@endif</span>
                            <span class="flex-grow-1" style="min-width:0">
                                <span class="row-title d-block text-truncate">{{ $report->description ?: 'Dépôt sauvage' }}</span>
                                <span class="pill st-{{ $report->status->value }} mt-1"><span class="dot"></span>{{ $report->status->getLabel() }}</span>
                                @if ($report->takerName() && $report->cleanup_at)<span class="row-sub d-block mt-1">{{ $report->takerName() }} · {{ $report->cleanup_at->translatedFormat('D j M, H\hi') }}</span>@endif
                            </span>
                        </a>
                        @if ($loop->last && $loop->count > 5)
                            </div>
                            <button class="btn btn-link btn-sm w-100 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#more-reports" aria-expanded="false">Voir les {{ $loop->count - 4 }} autres</button>
                        @endif
                    @empty
                        <div class="empty py-3"><strong>Aucun signalement</strong>Le bouton « + » permet d'en faire un en 20 secondes.</div>
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
            </div>

            {{-- Colonne latérale : classement, redevance, associations, réglages. --}}
            <div class="profile-col">
                <div class="section-title"><h2>Classement de {{ now()->translatedFormat('F') }}</h2></div>
                <div class="panel py-1">
                    @forelse ($leaderboard as $row)
                        <div class="row-item {{ $row['user']->is($user) ? 'is-me' : '' }}">
                            <span class="rank-badge rank-{{ $row['rank'] }}">{{ $row['rank'] }}</span>
                            <span class="avatar">{{ $row['user']->initials() }}</span>
                            <span class="row-title flex-grow-1">{{ $row['user']->shortName() }}</span>
                            <strong style="font-variant-numeric: tabular-nums">{{ $row['points'] }} pts</strong>
                        </div>
                    @empty
                        <div class="empty py-3"><strong>Personne pour l'instant</strong>Le premier dépôt nettoyé ce mois-ci prend la tête.</div>
                    @endforelse
                    <div class="row-sub py-2">10 pts par dépôt signalé (une fois nettoyé) · 30 pts par nettoyage ou ramassage</div>
                </div>

                @if ($user->isCitizen())
                    <div class="section-title"><h2>Ma redevance de collecte</h2></div>
                    <div class="panel py-1">
                        <form method="POST" action="{{ route('profile.home-village') }}" class="py-2">
                            @csrf @method('PATCH')
                            <label for="home_village_id" class="form-label">Mon village de résidence</label>
                            <div class="d-flex gap-2">
                                <select id="home_village_id" name="home_village_id" class="form-select">
                                    <option value="">Choisir…</option>
                                    @foreach ($villages as $village)
                                        <option value="{{ $village->id }}" @selected($user->home_village_id === $village->id)>{{ $village->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-soft">OK</button>
                            </div>
                            <div class="field-hint">{{ config('cleancomores.due_amount') }} {{ config('cleancomores.currency') }} à chaque passage du camion dans ce village.</div>
                        </form>
                        @forelse ($dues as $due)
                            <div class="row-item">
                                <span class="icon-chip {{ $due->status === DueStatus::Payee ? 'ico-leaf' : 'ico-red' }}"><i class="bi bi-receipt"></i></span>
                                <span class="flex-grow-1" style="min-width:0">
                                    <span class="row-title d-block">{{ $due->amountLabel() }} · {{ $due->passage->village->name }}</span>
                                    <span class="row-sub">{{ $due->passage->passed_on->translatedFormat('j F') }} · {{ $due->passage->truck->name }}</span>
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
    </div>
</div>
@endsection
