{{--
    Bloc « Je m'en occupe » partagé par les signalements et les demandes de collecte.
    $item (Report|PickupRequest), $isOpen, $takeRoute, $plannedLabel, $plannedAt, $ledAssociation, $routes (start?, postpone, finish, release)
--}}
@php($me = auth()->user())

@if ($isOpen)
    <form method="POST" action="{{ $takeRoute }}" class="engage mt-3" novalidate>
        @csrf
        <h2 class="mb-1"><i class="bi bi-hand-thumbs-up-fill text-warning me-1"></i> Je m'en occupe</h2>
        <p class="mb-3 text-muted">Indiquez quand vous passerez : tout le monde le verra sur la carte.</p>
        <label for="planned_at" class="form-label">Jour et heure</label>
        <input type="datetime-local" id="planned_at" name="planned_at" class="form-control mb-3" min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('planned_at', today()->addDay()->setTime(8, 0)->format('Y-m-d\TH:i')) }}" required>
        @if ($ledAssociation)
            <label for="association_id" class="form-label">Au nom de</label>
            <select id="association_id" name="association_id" class="form-select mb-3">
                <option value="">Moi-même</option>
                <option value="{{ $ledAssociation->id }}" @selected(old('association_id') == $ledAssociation->id)>{{ $ledAssociation->name }}</option>
            </select>
        @endif
        <label for="taker_note" class="form-label">Un mot pour les voisins <span class="fw-normal text-muted">(facultatif)</span></label>
        <input id="taker_note" name="taker_note" maxlength="300" class="form-control mb-3" value="{{ old('taker_note') }}" placeholder="Ex. : on vient à trois avec des sacs">
        <button class="btn btn-primary btn-lg w-100">Je m'engage</button>
    </form>
@elseif ($item->isTakenBy($me))
    <div class="panel mt-3">
        <div class="d-flex gap-3 align-items-center mb-3">
            <span class="icon-chip ico-amber"><i class="bi bi-person-check-fill"></i></span>
            <div>
                <div class="fw-bold">Vous vous en occupez</div>
                <div class="row-sub">{{ $plannedLabel }}</div>
            </div>
        </div>
        <div class="d-grid gap-2">
            @isset($routes['start'])
                <form method="POST" action="{{ $routes['start'] }}">@csrf<button class="btn btn-soft w-100"><i class="bi bi-play-fill me-1"></i> Je commence maintenant</button></form>
            @endisset
            <form method="POST" action="{{ $routes['finish'] }}">@csrf<button class="btn btn-success btn-lg w-100"><i class="bi bi-check2-circle me-1"></i> C'est fait !</button></form>
            <button class="btn btn-ghost w-100" type="button" data-bs-toggle="collapse" data-bs-target="#postpone">Pas fini ? Prévenir et changer la date</button>
        </div>
        <form method="POST" action="{{ $routes['postpone'] }}" class="collapse mt-3" id="postpone">
            @csrf
            <label class="form-label" for="new_date">Nouvelle date</label>
            <input type="datetime-local" id="new_date" name="planned_at" class="form-control mb-2" min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ $plannedAt?->format('Y-m-d\TH:i') }}" required>
            <label class="form-label" for="why">Ce qu'il reste à faire</label>
            <input id="why" name="taker_note" maxlength="300" class="form-control mb-2" placeholder="Ex. : il reste des gravats, on revient avec une brouette" required>
            <button class="btn btn-primary w-100">Prévenir les voisins</button>
        </form>
        <form method="POST" action="{{ $routes['release'] }}" class="text-center mt-2" onsubmit="return confirm('Laisser ce nettoyage à quelqu\'un d\'autre ?')">
            @csrf
            <button class="btn btn-link btn-sm text-muted">Je ne peux plus m'en occuper</button>
        </form>
    </div>
@endif
