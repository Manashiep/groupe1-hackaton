@extends('layouts.app')

@section('title', 'Gestion participative des déchets aux Comores')

@section('fullwidth')
<section class="hero py-5">
    <div class="container py-lg-4 position-relative" style="z-index:1">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <span class="badge rounded-pill bg-white bg-opacity-10 text-white px-3 py-2 mb-3"><i class="bi bi-geo-alt-fill text-warning me-1"></i> Commune pilote · Moroni, Ngazidja</span>
                <h1>Des quartiers propres,<br>ça s'organise ensemble.</h1>
                <p class="lead mt-3">Sachez quand passe la camionnette, signalez un dépôt sauvage en une photo et suivez son nettoyage. Habitants, agents et mairie sur une seule plateforme.</p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="{{ auth()->user()?->isCitizen() ? route('citizen.reports.create') : route('register') }}" class="btn btn-sun btn-lg"><i class="bi bi-camera-fill me-1"></i> Signaler des déchets</a>
                    <a href="{{ route('map') }}" class="btn btn-outline-light btn-lg"><i class="bi bi-map me-1"></i> Voir la carte</a>
                </div>
                <div class="row stat-strip mt-5 g-4">
                    <div class="col-4"><div class="num tabular">{{ $stats['resolved'] }}</div><div class="lbl">dépôts nettoyés</div></div>
                    <div class="col-4"><div class="num tabular">{{ $stats['collections'] }}</div><div class="lbl">collectes sur 30 jours</div></div>
                    <div class="col-4"><div class="num tabular">{{ $stats['quartiers'] }}</div><div class="lbl">quartiers couverts</div></div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-card p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="feature-icon"><i class="bi bi-truck"></i></span>
                        <div>
                            <h2 class="h5 mb-0">Quand passe la camionnette ?</h2>
                            <div class="small text-muted">Choisissez votre quartier ou village</div>
                        </div>
                    </div>
                    <form action="{{ route('calendar') }}" method="GET">
                        <label for="hero-quartier" class="visually-hidden">Quartier</label>
                        <select id="hero-quartier" name="quartier" class="form-select form-select-lg mb-3" required>
                            <option value="">Sélectionner…</option>
                            @foreach ($quartiers->groupBy('ville') as $ville => $items)
                                <optgroup label="{{ $ville }}">
                                    @foreach ($items as $q)
                                        <option value="{{ $q->slug }}" @selected(auth()->user()?->quartier_id === $q->id)>{{ $q->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <button class="btn btn-primary btn-lg w-100">Voir le calendrier de collecte</button>
                    </form>
                    <div class="small text-muted mt-3"><i class="bi bi-bell me-1"></i> Créez un compte pour recevoir un rappel la veille du passage.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <div class="small-caps">Une plateforme, quatre services</div>
            <h2 class="mt-1">Tout le cycle des déchets du quartier</h2>
        </div>
        <div class="row g-3">
            @foreach ([
                ['bi-calendar-week', 'Collectes régulières', 'Calendrier par quartier, rappel la veille, statut du passage en direct (programmée, en cours, effectuée).'],
                ['bi-camera', 'Signalement citoyen', 'Une photo, un point sur la carte, une description : le service propreté est prévenu immédiatement.'],
                ['bi-clipboard-check', 'Interventions suivies', 'Les responsables affectent une équipe, les agents confirment le nettoyage, l\'habitant suit chaque étape.'],
                ['bi-graph-up-arrow', 'Pilotage & statistiques', 'Indice de propreté, zones les plus touchées, points noirs récurrents : des décisions basées sur des faits.'],
            ] as [$icon, $title, $text])
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <span class="feature-icon mb-3"><i class="bi {{ $icon }}"></i></span>
                            <h3 class="h6">{{ $title }}</h3>
                            <p class="small text-muted mb-0">{{ $text }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="small-caps">Comment ça marche</div>
                        <h2 class="h4 mt-1 mb-4">Un dépôt sauvage traité en 3 étapes</h2>
                        @foreach ([
                            ['Je signale', 'Photo, localisation sur la carte et courte description depuis mon téléphone.'],
                            ['La mairie vérifie et planifie', 'Le responsable confirme le problème (+10 points pour moi) et affecte une équipe.'],
                            ['L\'équipe nettoie', 'L\'agent confirme sur le terrain, je reçois une notification « Zone nettoyée ».'],
                        ] as $i => [$title, $text])
                            <div class="d-flex gap-3 mb-3">
                                <span class="step-num">{{ $i + 1 }}</span>
                                <div><strong>{{ $title }}</strong><div class="small text-muted">{{ $text }}</div></div>
                            </div>
                        @endforeach
                        <a href="{{ auth()->user()?->isCitizen() ? route('citizen.reports.create') : route('register') }}" class="btn btn-primary mt-2">Je participe</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-stars text-warning me-1"></i> Indice de propreté des quartiers</span>
                        <a href="{{ route('map') }}" class="small fw-semibold text-decoration-none">Carte détaillée</a>
                    </div>
                    <div class="card-body pt-2">
                        <p class="small text-muted">Calculé uniquement sur des données vérifiées : collectes confirmées par les agents et dépôts validés par la mairie (30 derniers jours).</p>
                        <div class="row g-0">
                            @foreach ($ranking as $row)
                                <div class="col-xl-6 pe-xl-3">
                                    <a href="{{ route('quartiers.show', $row['quartier']) }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-decoration-none text-body">
                                        <span class="fw-semibold text-truncate flex-shrink-0" style="width:100px">{{ $row['quartier']->name }}</span>
                                        <x-score :data="$row" class="flex-grow-1" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@if ($leaders->isNotEmpty())
<section class="pb-5">
    <div class="container">
        <div class="card" style="background: var(--cc-sun-soft); border-color: #f3e2a6">
            <div class="card-body p-4 d-lg-flex align-items-center gap-5">
                <div class="mb-3 mb-lg-0" style="max-width: 360px">
                    <div class="small-caps" style="color:#8a6a00">Citoyens engagés</div>
                    <h2 class="h4 mt-1">Chaque signalement vérifié rapporte 10 points</h2>
                    <p class="small mb-0 text-muted">Les points récompensent la participation à la propreté du quartier. Des récompenses locales pourront être proposées avec la mairie.</p>
                </div>
                <ol class="leaderboard list-unstyled mb-0 flex-grow-1">
                    @foreach ($leaders as $i => $leader)
                        @php($parts = explode(' ', $leader->name))
                        <li>
                            <span class="step-num" style="background:{{ $i === 0 ? '#f5b301' : '#fff' }}">{{ $i + 1 }}</span>
                            <span class="fw-semibold">{{ $parts[0] }} {{ isset($parts[1]) ? mb_substr($parts[1], 0, 1).'.' : '' }}</span>
                            <span class="text-muted small">{{ $leader->quartier?->name }}</span>
                            <span class="ms-auto fw-bold tabular">{{ $leader->points }} pts</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</section>
@endif

<section class="pb-5">
    <div class="container text-center">
        <p class="h4 fw-bold mb-1">« Une ville propre commence par une communauté engagée. »</p>
        <p class="text-muted">Ensemble, faisons de nos quartiers et de nos villages des espaces plus propres et plus durables.</p>
    </div>
</section>
@endsection
