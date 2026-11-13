@extends('layouts.app')

@section('title', 'Associations')

@use('App\Enums\MembershipStatus')

@section('content')
<div class="hero">
    <div class="inner">
        <h1>Associations</h1>
        <p class="mb-3">Les groupes de nettoyeurs de vos villages : leurs annonces, leurs actions, et comment les rejoindre.</p>
        <nav class="segmented" aria-label="Affichage">
            <a href="{{ route('associations.index') }}" class="{{ $tab === 'actu' ? 'active' : '' }}">Actualités</a>
            <a href="{{ route('associations.index', ['onglet' => 'annuaire']) }}" class="{{ $tab === 'annuaire' ? 'active' : '' }}">Annuaire</a>
        </nav>
    </div>
</div>

<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        @if ($tab === 'actu')
            @forelse ($posts as $post)
                @include('associations._post', ['post' => $post])
            @empty
                <div class="empty"><span class="icon-chip"><i class="bi bi-chat-square-heart"></i></span><strong>Pas encore de publication</strong>Les associations partageront ici leurs nettoyages.</div>
            @endforelse
        @else
            <div class="panel py-1">
                @forelse ($associations as $association)
                    @php($status = $myStatuses[$association->id] ?? null)
                    <div class="row-item">
                        <a href="{{ route('associations.show', $association) }}" class="asso-avatar text-decoration-none" style="background: {{ $association->color }}">{{ $association->initials() }}</a>
                        <a href="{{ route('associations.show', $association) }}" class="flex-grow-1 text-decoration-none text-body" style="min-width:0">
                            <span class="row-title d-block">{{ $association->name }}</span>
                            <span class="row-sub">{{ $association->village?->name }} · {{ $association->members_count }} membre{{ $association->members_count > 1 ? 's' : '' }}</span>
                        </a>
                        @if ($status === MembershipStatus::Membre)
                            <span class="pill st-nettoye"><i class="bi bi-check2"></i> Membre</span>
                        @elseif ($status === MembershipStatus::EnAttente)
                            <span class="pill pill-info">Demande envoyée</span>
                        @else
                            <form method="POST" action="{{ route('associations.join', $association) }}">@csrf<button class="btn btn-primary btn-sm">Rejoindre</button></form>
                        @endif
                    </div>
                @empty
                    <div class="empty"><strong>Aucune association pour l'instant</strong>Soyez le premier à en créer une.</div>
                @endforelse
            </div>
            <a href="{{ route('associations.create') }}" class="btn btn-soft w-100 mt-3"><i class="bi bi-plus-circle me-1"></i> Créer une association</a>
        @endif
    </div>
</div>
@endsection
