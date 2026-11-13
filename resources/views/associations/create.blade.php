@extends('layouts.app')

@section('title', 'Créer une association')

@section('content')
<div class="hero">
    <div class="inner">
        <a href="{{ route('associations.index', ['onglet' => 'annuaire']) }}" class="btn btn-ghost btn-sm mb-3"><i class="bi bi-arrow-left"></i> Associations</a>
        <h1>Créer une association</h1>
        <p>La mairie valide chaque groupe. Une fois acceptée, vous en devenez le chef : vous gérez les membres et publiez les annonces.</p>
    </div>
</div>

<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        @if ($pending)
            <div class="callout"><i class="bi bi-hourglass-split"></i><div><strong>{{ $pending->name }}</strong> est en attente de validation par la mairie. Vous serez prévenu dans votre espace « Moi ».</div></div>
        @else
            <form method="POST" action="{{ route('associations.store') }}" novalidate>
                @csrf
                <label for="name" class="form-label">Nom du groupe</label>
                <input id="name" name="name" value="{{ old('name') }}" maxlength="80" class="form-control mb-3" placeholder="Ex. : Les Jeunes de Mvouni" required>

                <label for="village_id" class="form-label">Village principal</label>
                <select id="village_id" name="village_id" class="form-select mb-3">
                    <option value="">Plusieurs villages</option>
                    @foreach ($villages as $village)
                        <option value="{{ $village->id }}" @selected(old('village_id') == $village->id)>{{ $village->name }}</option>
                    @endforeach
                </select>

                <label for="description" class="form-label">Présentation <span class="text-muted fw-normal">(visible par tous)</span></label>
                <textarea id="description" name="description" rows="3" maxlength="1000" class="form-control mb-3" placeholder="Qui êtes-vous, que nettoyez-vous, quand vous retrouvez-vous ?" required>{{ old('description') }}</textarea>

                <label for="motivation" class="form-label">Message à la mairie</label>
                <textarea id="motivation" name="motivation" rows="3" maxlength="1000" class="form-control mb-3" placeholder="Combien êtes-vous, qu'avez-vous déjà fait ?" required>{{ old('motivation') }}</textarea>

                <div class="form-label">Couleur du groupe</div>
                <div class="d-flex gap-2 mb-4" role="radiogroup" aria-label="Couleur du groupe">
                    @foreach ($colors as $color)
                        <label class="d-inline-flex" style="cursor:pointer">
                            <input type="radio" name="color" value="{{ $color }}" class="visually-hidden color-radio" @checked(old('color', $colors[0]) === $color)>
                            <span class="asso-avatar color-swatch" style="background: {{ $color }}"><i class="bi bi-check-lg"></i></span>
                        </label>
                    @endforeach
                </div>

                <button class="btn btn-primary btn-lg w-100">Envoyer la demande à la mairie</button>
            </form>
        @endif
    </div>
</div>

@push('head')
<style>
    .color-swatch i { opacity: 0; transition: opacity .15s; }
    .color-radio:checked + .color-swatch { box-shadow: 0 0 0 3px #fff, 0 0 0 5px var(--ink); }
    .color-radio:checked + .color-swatch i { opacity: 1; }
    .color-radio:focus-visible + .color-swatch { outline: 3px solid rgba(31, 95, 208, .45); outline-offset: 3px; }
</style>
@endpush
@endsection
