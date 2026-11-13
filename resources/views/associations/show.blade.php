@extends('layouts.app')

@section('title', $association->name)

@use('App\Enums\MembershipStatus')
@use('App\Enums\AssociationStatus')

@section('content')
<div class="cover" style="--asso: {{ $association->color }}">
    <div class="inner">
        <a href="{{ route('associations.index', ['onglet' => 'annuaire']) }}" class="btn btn-ghost btn-sm mb-3"><i class="bi bi-arrow-left"></i> Associations</a>
        <div class="d-flex align-items-center gap-3">
            <span class="asso-avatar lg" style="background: {{ $association->color }}">{{ $association->initials() }}</span>
            <div>
                <h1 class="h2 mb-1">{{ $association->name }}</h1>
                <div class="text-muted"><i class="bi bi-geo-alt"></i> {{ $association->village?->name ?? 'Plusieurs villages' }} · {{ $association->members_count }} membre{{ $association->members_count > 1 ? 's' : '' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        <p class="fs-6 text-ink-2">{{ $association->description }}</p>
        <p class="row-sub mb-3">Chef du groupe : {{ $association->leader->shortName() }}</p>

        @if ($association->status !== AssociationStatus::Active)
            <div class="callout mb-3"><i class="bi bi-hourglass-split"></i><div>{{ $association->status->getLabel() }}</div></div>
        @elseif ($isLeader)
            <a href="{{ route('associations.manage.show', $association) }}" class="btn btn-primary btn-lg w-100 mb-3"><i class="bi bi-sliders me-1"></i> Gérer mon association</a>
        @elseif ($membership?->status === MembershipStatus::Membre)
            <div class="d-flex gap-2 align-items-center mb-3">
                <span class="pill st-nettoye"><i class="bi bi-check2"></i> Vous êtes membre</span>
                <form method="POST" action="{{ route('associations.leave', $association) }}" class="ms-auto" onsubmit="return confirm('Quitter ce groupe ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-link btn-sm text-muted">Quitter</button>
                </form>
            </div>
        @elseif ($membership?->status === MembershipStatus::EnAttente)
            <div class="callout callout-info mb-3"><i class="bi bi-hourglass-split"></i><div>Demande envoyée. Le chef du groupe va vous répondre.</div></div>
        @else
            <form method="POST" action="{{ route('associations.join', $association) }}" class="mb-3">
                @csrf
                <button class="btn btn-primary btn-lg w-100"><i class="bi bi-person-plus-fill me-1"></i> Rejoindre l'association</button>
            </form>
        @endif

        <div class="section-title"><h2>Publications</h2></div>
        @forelse ($posts as $post)
            @include('associations._post', ['post' => $post->setRelation('association', $association)])
        @empty
            <div class="empty"><span class="icon-chip"><i class="bi bi-camera-reels"></i></span><strong>Rien de publié pour l'instant</strong>Les annonces et photos de nettoyage apparaîtront ici.</div>
        @endforelse
    </div>
</div>
@endsection
