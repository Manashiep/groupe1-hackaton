@extends('layouts.app')

@section('title', 'Gérer '.$association->name)

@section('content')
<div class="cover" style="--asso: {{ $association->color }}">
    <div class="inner">
        <a href="{{ route('associations.show', $association) }}" class="btn btn-ghost btn-sm mb-3"><i class="bi bi-arrow-left"></i> Page publique</a>
        <div class="d-flex align-items-center gap-3">
            <span class="asso-avatar lg" style="background: {{ $association->color }}">{{ $association->initials() }}</span>
            <div>
                <div class="text-muted">Espace du chef de groupe</div>
                <h1 class="h2 mb-0">{{ $association->name }}</h1>
            </div>
        </div>
    </div>
</div>

<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        <div class="section-title mt-0">
            <h2>Demandes d'adhésion @if ($requests->isNotEmpty())<span class="pill st-nouveau ms-1">{{ $requests->count() }}</span>@endif</h2>
        </div>
        <div class="panel py-1">
            @forelse ($requests as $request)
                <div class="row-item">
                    <span class="avatar">{{ $request->user->initials() }}</span>
                    <div class="flex-grow-1"><div class="row-title">{{ $request->user->name }}</div><div class="row-sub">Demande {{ $request->created_at->diffForHumans() }}</div></div>
                    <form method="POST" action="{{ route('associations.manage.members.refuse', [$association, $request]) }}">@csrf<button class="btn btn-ghost btn-sm" aria-label="Refuser"><i class="bi bi-x-lg"></i></button></form>
                    <form method="POST" action="{{ route('associations.manage.members.accept', [$association, $request]) }}">@csrf<button class="btn btn-primary btn-sm">Accepter</button></form>
                </div>
            @empty
                <div class="empty py-3"><strong>Aucune demande en attente</strong>Les citoyens qui veulent vous rejoindre apparaîtront ici.</div>
            @endforelse
        </div>

        <div class="section-title" id="publier"><h2>Publier</h2></div>
        <form method="POST" action="{{ route('associations.manage.posts.store', $association) }}" enctype="multipart/form-data" class="panel" novalidate>
            @csrf
            <div class="d-flex gap-2 mb-3" role="radiogroup" aria-label="Type de publication">
                <div class="kind-option flex-fill">
                    <input type="radio" name="type" value="annonce" id="type-annonce" @checked(old('type', 'annonce') === 'annonce')>
                    <label for="type-annonce" class="justify-content-center"><i class="bi bi-megaphone-fill text-warning"></i> Annonce</label>
                </div>
                <div class="kind-option flex-fill">
                    <input type="radio" name="type" value="publication" id="type-publication" @checked(old('type') === 'publication')>
                    <label for="type-publication" class="justify-content-center"><i class="bi bi-camera-fill text-primary"></i> Publication</label>
                </div>
            </div>
            <textarea name="body" rows="3" maxlength="2000" class="form-control mb-3" placeholder="Ex. : grand nettoyage de la plage samedi, venez nombreux !" required>{{ old('body') }}</textarea>
            <div id="event-field">
                <label for="event_at" class="form-label">Date du rendez-vous</label>
                <input type="datetime-local" id="event_at" name="event_at" value="{{ old('event_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" class="form-control mb-3">
            </div>
            <label for="media" class="form-label">Photo ou vidéo <span class="text-muted fw-normal">(facultatif)</span></label>
            <input type="file" id="media" name="media" accept="image/*,video/mp4,video/webm,video/quicktime" class="form-control mb-3">
            <button class="btn btn-primary w-100"><i class="bi bi-send-fill me-1"></i> Publier</button>
        </form>

        <div class="section-title"><h2>Membres <span class="text-muted fw-normal">· {{ $members->count() }}</span></h2></div>
        <div class="panel py-1">
            @foreach ($members as $member)
                <div class="row-item">
                    <span class="avatar">{{ $member->user->initials() }}</span>
                    <div class="flex-grow-1"><div class="row-title">{{ $member->user->name }}</div><div class="row-sub">{{ $member->user_id === $association->leader_id ? 'Chef du groupe' : 'Membre depuis '.$member->updated_at->translatedFormat('F Y') }}</div></div>
                    @if ($member->user_id !== $association->leader_id)
                        <form method="POST" action="{{ route('associations.manage.members.refuse', [$association, $member]) }}" onsubmit="return confirm('Retirer {{ $member->user->shortName() }} du groupe ?')">
                            @csrf
                            <button class="btn btn-ghost btn-sm">Retirer</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="section-title"><h2>Vos publications</h2></div>
        @forelse ($posts as $post)
            @include('associations._post', ['post' => $post->setRelation('association', $association), 'deleteRoute' => route('associations.manage.posts.destroy', [$association, $post])])
        @empty
            <div class="empty"><strong>Rien de publié pour l'instant</strong>Annoncez votre prochain nettoyage ci-dessus.</div>
        @endforelse

        <div class="section-title"><h2>Infos du groupe</h2></div>
        <form method="POST" action="{{ route('associations.manage.update', $association) }}" class="panel">
            @csrf @method('PATCH')
            <label for="description" class="form-label">Présentation</label>
            <textarea id="description" name="description" rows="3" maxlength="1000" class="form-control mb-3">{{ old('description', $association->description) }}</textarea>
            <div class="form-label">Couleur</div>
            <div class="d-flex gap-2 mb-3">
                @foreach ($colors as $color)
                    <label style="cursor:pointer">
                        <input type="radio" name="color" value="{{ $color }}" class="visually-hidden color-radio" @checked($association->color === $color)>
                        <span class="asso-avatar color-swatch" style="background: {{ $color }}"><i class="bi bi-check-lg"></i></span>
                    </label>
                @endforeach
            </div>
            <button class="btn btn-soft w-100">Enregistrer</button>
        </form>
    </div>
</div>

@push('head')
<style>
    .color-swatch i { opacity: 0; transition: opacity .15s; }
    .color-radio:checked + .color-swatch { box-shadow: 0 0 0 3px #fff, 0 0 0 5px var(--ink); }
    .color-radio:checked + .color-swatch i { opacity: 1; }
</style>
@endpush
@endsection

@push('scripts')
<script>
const eventField = document.getElementById('event-field');
const sync = () => eventField.classList.toggle('d-none', !document.getElementById('type-annonce').checked);
document.querySelectorAll('input[name="type"]').forEach((r) => r.addEventListener('change', sync));
sync();
</script>
@endpush
