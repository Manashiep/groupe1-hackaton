{{--
    Bloc « Je m'en occupe » partagé par les signalements et les demandes de collecte.
    $item (Report|PickupRequest), $isOpen, $isDone, $takeRoute, $plannedLabel, $plannedAt, $ledAssociation, $routes (start?, postpone, finish, release)
--}}
@php($me = auth()->user())

@if ($isDone && $item->proofPhotoUrl())
    <figure class="panel mt-3 mb-0">
        <img src="{{ $item->proofPhotoUrl() }}" alt="Photo de l'endroit après le passage" class="w-100" style="max-height:280px;object-fit:cover;border-radius:var(--radius)">
        <figcaption class="row-sub mt-2"><i class="bi bi-patch-check-fill text-success"></i> Preuve envoyée par {{ $item->takerName() }}</figcaption>
    </figure>
@endif

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
@elseif ($item->isTakenBy($me) && ! $isDone)
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
            <button class="btn btn-success btn-lg w-100" type="button" data-bs-toggle="collapse" data-bs-target="#finish"><i class="bi bi-check2-circle me-1"></i> C'est fait !</button>
            <button class="btn btn-ghost w-100" type="button" data-bs-toggle="collapse" data-bs-target="#postpone">Pas fini ? Prévenir et changer la date</button>
        </div>
        {{-- Preuve obligatoire : sans photo « après », pas de « Nettoyé » ni de points. --}}
        <form method="POST" action="{{ $routes['finish'] }}" enctype="multipart/form-data" class="collapse mt-3 @if ($errors->has('proof_photo')) show @endif" id="finish">
            @csrf
            <label for="proof_photo" class="d-block mb-2" style="cursor:pointer">
                <span class="panel d-flex align-items-center gap-3" id="proof-placeholder">
                    <span class="icon-chip ico-leaf"><i class="bi bi-camera-fill"></i></span>
                    <span><strong class="d-block">Photo de l'endroit propre</strong><span class="text-muted small">C'est la preuve pour les voisins et la mairie.</span></span>
                </span>
                <img id="proof-preview" alt="Aperçu de la photo" class="d-none w-100" style="max-height:240px;object-fit:cover;border-radius:var(--radius)">
            </label>
            <input type="file" name="proof_photo" id="proof_photo" accept="image/*" capture="environment" class="visually-hidden" required>
            @error('proof_photo')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
            <button class="btn btn-success w-100"><i class="bi bi-send-fill me-1"></i> Envoyer la preuve</button>
        </form>
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

@if ($item->isTakenBy($me) && ! $isDone)
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        CC.compressPhotoInput(document.getElementById('proof_photo'), (url) => {
            const preview = document.getElementById('proof-preview');
            preview.src = url;
            preview.classList.remove('d-none');
            document.getElementById('proof-placeholder').classList.add('d-none');
        });
    });
    </script>
    @endpush
@endif
