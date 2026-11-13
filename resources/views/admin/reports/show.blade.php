@extends('layouts.admin')

@section('title', $report->reference().' · '.$report->category->label())

@include('partials.leaflet')

@use('App\Enums\ReportStatus')

@section('content')
<a href="{{ route('admin.reports.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Tous les signalements</a>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-3">
    <div>
        <h1 class="h3 mb-0">{{ $report->category->label() }} · {{ $report->quartier?->name }}</h1>
        <div class="text-muted small">{{ $report->reference() }} · reçu {{ $report->created_at->translatedFormat('l j F Y à H\hi') }} ({{ $report->created_at->diffForHumans() }})</div>
    </div>
    <x-status :status="$report->status" class="fs-6" />
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card mb-4">
            <div class="row g-0">
                <div class="col-md-6">
                    <div class="photo-frame h-100" style="border-radius: var(--cc-radius) 0 0 var(--cc-radius)">
                        @if ($report->photoUrl())
                            <a href="{{ $report->photoUrl() }}" target="_blank" rel="noopener" class="w-100 h-100"><img src="{{ $report->photoUrl() }}" alt="Photo du signalement"></a>
                        @else
                            <span class="text-center"><i class="bi bi-camera-video-off fs-1 d-block"></i>Pas de photo</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-body">
                        <p>{{ $report->description }}</p>
                        <dl class="small mb-0">
                            <dt class="text-muted fw-normal">Repère</dt><dd>{{ $report->address ?? '—' }}</dd>
                            <dt class="text-muted fw-normal">Coordonnées</dt><dd class="tabular">{{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</dd>
                            <dt class="text-muted fw-normal">Signalé par</dt>
                            <dd class="mb-0">{{ $report->user?->name ?? 'Compte supprimé' }} @if($report->user?->phone)<br><a href="tel:{{ $report->user->phone }}">{{ $report->user->phone }}</a>@endif</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <div id="map" class="map mb-4" style="height: 300px"></div>

        @if ($nearby->isNotEmpty())
            <div class="card">
                <div class="card-header"><i class="bi bi-arrow-repeat me-1"></i> Signalements à proximité (≈ 150 m, 90 jours)</div>
                <ul class="list-group list-group-flush small">
                    @foreach ($nearby as $other)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.reports.show', $other) }}" class="text-decoration-none">{{ $other->reference() }} · {{ $other->category->label() }}</a>
                            <span class="text-muted">{{ $other->created_at->diffForHumans() }}</span>
                            <x-status :status="$other->status" />
                        </li>
                    @endforeach
                </ul>
                <div class="card-body small text-muted pt-2">Plusieurs dépôts au même endroit : possible doublon, ou point noir récurrent à traiter durablement.</div>
            </div>
        @endif
    </div>

    <div class="col-xl-5">
        {{-- Actions --}}
        @if ($report->status === ReportStatus::Nouveau)
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning-subtle">1. Vérifier le signalement</div>
                <div class="card-body">
                    <p class="small text-muted">La photo et la position correspondent-elles à un vrai problème ? La vérification crédite l'habitant de 10 points.</p>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('admin.reports.status', $report) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="action" value="valider">
                            <button class="btn btn-primary"><i class="bi bi-patch-check me-1"></i> Valider</button>
                        </form>
                        <button class="btn btn-soft" data-bs-toggle="collapse" data-bs-target="#reject-form">Rejeter…</button>
                    </div>
                    <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="collapse mt-3" id="reject-form">
                        @csrf @method('PATCH')
                        <input type="hidden" name="action" value="rejeter">
                        <label for="note" class="form-label small">Motif (envoyé à l'habitant)</label>
                        <textarea id="note" name="note" class="form-control mb-2" rows="2" placeholder="Ex. : doublon du signalement SIG-00012, déjà planifié."></textarea>
                        <button class="btn btn-danger btn-sm">Confirmer le rejet</button>
                    </form>
                </div>
            </div>
        @endif

        @if (in_array($report->status, [ReportStatus::Nouveau, ReportStatus::Valide], true))
            <div class="card mb-4">
                <div class="card-header">{{ $report->status === ReportStatus::Nouveau ? '2. ' : '' }}Planifier une intervention</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.reports.intervention', $report) }}" class="row g-2">
                        @csrf
                        <div class="col-sm-6">
                            <label class="form-label small" for="team_id">Équipe</label>
                            <select id="team_id" name="team_id" class="form-select">
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected($suggestedTeam === $team->id)>{{ $team->name }}{{ $suggestedTeam === $team->id ? ' (secteur)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small" for="priority">Priorité</label>
                            <select id="priority" name="priority" class="form-select">
                                @foreach (\App\Models\Intervention::PRIORITIES as $value => $p)
                                    <option value="{{ $value }}" @selected($value === ($report->category === \App\Enums\ReportCategory::DechetsDangereux ? 'urgente' : 'normale'))>{{ $p['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small" for="scheduled_at">Date et heure</label>
                            <input id="scheduled_at" type="datetime-local" name="scheduled_at" class="form-control" value="{{ now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small" for="notes">Consignes pour l'équipe</label>
                            <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Matériel, accès, précautions…"></textarea>
                        </div>
                        <div class="col-12"><button class="btn btn-primary w-100 mt-1"><i class="bi bi-clipboard-check me-1"></i> Affecter l'intervention</button></div>
                    </form>
                </div>
            </div>
        @endif

        @if ($report->interventions->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">Interventions</div>
                <ul class="list-group list-group-flush small">
                    @foreach ($report->interventions as $intervention)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $intervention->team?->name }}</strong>
                                <x-status :status="$intervention->status" />
                            </div>
                            <div class="text-muted">Prévue {{ $intervention->scheduled_at->translatedFormat('l j F à H\hi') }}</div>
                            @if ($intervention->completion_note)<div class="mt-1">{{ $intervention->completion_note }}</div>@endif
                            @if ($intervention->completionPhotoUrl())<img src="{{ $intervention->completionPhotoUrl() }}" alt="Après nettoyage" class="img-fluid rounded mt-2">@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header">Historique</div>
            <div class="card-body">
                <ul class="timeline">
                    @foreach ($report->events as $event)
                        <li>
                            <span class="t-icon bg-{{ $event->status?->color() ?? 'secondary' }}-subtle text-{{ $event->status?->color() ?? 'secondary' }}-emphasis"><i class="bi {{ $event->status?->icon() ?? 'bi-dot' }}"></i></span>
                            <div class="fw-semibold small">{{ $event->status?->label() }} <span class="text-muted fw-normal">· {{ $event->user?->name }}</span></div>
                            @if ($event->note)<div class="small">{{ $event->note }}</div>@endif
                            <div class="small text-muted">{{ $event->created_at->translatedFormat('j M Y, H\hi') }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if ($report->status->isOpen() && $report->status !== ReportStatus::Nouveau)
                <form method="POST" action="{{ route('admin.reports.status', $report) }}" onsubmit="return confirm('Clôturer ce signalement comme nettoyé ?')">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="resoudre">
                    <button class="btn btn-soft btn-sm"><i class="bi bi-check2-circle me-1"></i> Clôturer manuellement</button>
                </form>
            @endif
            @if (in_array($report->status, [ReportStatus::Resolu, ReportStatus::Rejete], true))
                <form method="POST" action="{{ route('admin.reports.status', $report) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="rouvrir">
                    <button class="btn btn-soft btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i> Rouvrir (problème réapparu)</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const map = CC.baseMap('map', { center: [{{ $report->latitude }}, {{ $report->longitude }}], zoom: 17 });
    L.marker([{{ $report->latitude }}, {{ $report->longitude }}], { icon: CC.pin('{{ $report->status->hex() }}', 22) }).addTo(map);
    L.circle([{{ $report->latitude }}, {{ $report->longitude }}], { radius: 150, color: '#0f766e', weight: 1, fillOpacity: .04, dashArray: '4 6' }).addTo(map);
    @foreach ($nearby as $other)
        L.marker([{{ $other->latitude }}, {{ $other->longitude }}], { icon: CC.pin('{{ $other->status->hex() }}', 14) }).addTo(map).bindPopup(@js($other->reference().' · '.$other->category->label()));
    @endforeach
});
</script>
@endpush
