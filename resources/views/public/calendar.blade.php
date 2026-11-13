@extends('layouts.app')

@section('title', $selected ? 'Collecte à '.$selected->name : 'Calendrier de collecte')

@section('content')
<div class="page-head d-md-flex justify-content-between align-items-end gap-3">
    <div>
        <h1>Calendrier de collecte</h1>
        <p class="text-muted mb-0">Jours et horaires de passage des camionnettes, quartier par quartier.</p>
    </div>
    <form method="GET" class="mt-3 mt-md-0" style="min-width: 260px">
        <label for="quartier" class="form-label small text-muted mb-1">Votre quartier</label>
        <select id="quartier" name="quartier" class="form-select" onchange="this.form.submit()">
            <option value="">Choisir un quartier…</option>
            @foreach ($quartiers->groupBy('ville') as $ville => $items)
                <optgroup label="{{ $ville }}">
                    @foreach ($items as $q)
                        <option value="{{ $q->slug }}" @selected($selected?->id === $q->id)>{{ $q->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <noscript><button class="btn btn-primary btn-sm mt-2">Afficher</button></noscript>
    </form>
</div>

@if ($selected)
    <div class="row g-4">
        <div class="col-lg-5">
            @include('partials.next-collection', ['next' => $next, 'quartier' => $selected])
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Semaine type à {{ $selected->name }}</span>
                    <a href="{{ route('quartiers.show', $selected) }}" class="small fw-semibold text-decoration-none">Fiche du quartier <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    @include('partials.collection-week', ['schedules' => $schedules])
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Passages des deux prochaines semaines</span>
                    @guest
                        <a href="{{ route('register') }}" class="small fw-semibold text-decoration-none"><i class="bi bi-bell"></i> Recevoir les rappels</a>
                    @endguest
                </div>
                @include('partials.collection-list', ['collections' => $upcoming])
            </div>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach ($quartiers as $q)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('calendar', ['quartier' => $q->slug]) }}" class="card h-100 text-decoration-none text-body">
                    <div class="card-body">
                        <i class="bi bi-geo-alt text-primary"></i>
                        <div class="fw-bold mt-1">{{ $q->name }}</div>
                        <div class="small text-muted">{{ $q->ville }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection
