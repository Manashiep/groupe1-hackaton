@extends('layouts.app')

@section('title', 'Camion')

@section('content')
<div class="page">
    <h1 class="mb-3">Passage du camion</h1>

    @include('partials.flash')

    <div class="village-chips mb-4" role="list" aria-label="Villages">
        @foreach ($villages as $v)
            <a role="listitem" href="{{ route('trucks.index', ['village' => $v->slug]) }}" class="{{ $village?->id === $v->id ? 'active' : '' }}">{{ $v->name }}</a>
        @endforeach
    </div>

    @if (! $village)
        <div class="empty"><i class="bi bi-hand-index"></i>Choisissez votre village.</div>
    @else
        @forelse ($trucks as $truck)
            @php($next = $truck->upcomingPassages(14)->first())
            <div class="card mb-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="brand-mark" style="width:44px;height:44px;background: {{ $truck->color }}"><i class="bi bi-truck fs-5"></i></span>
                        <div>
                            <div class="fw-bold">{{ $truck->name }}</div>
                            <div class="small text-muted">{{ $village->name }} @if ($truck->plate) · {{ $truck->plate }} @endif</div>
                        </div>
                    </div>

                    @if ($next)
                        <div class="rounded-4 p-3 mb-3" style="background: var(--cc-green-soft)">
                            <div class="small text-muted">Prochain passage</div>
                            <div class="fs-5 fw-bold">
                                {{ $next['date']->isToday() ? 'Aujourd\'hui' : ($next['date']->isTomorrow() ? 'Demain' : ucfirst($next['date']->translatedFormat('l j F'))) }}
                                · {{ $next['schedule']->timeRange() }}
                            </div>
                        </div>
                    @endif

                    @forelse ($truck->schedules as $schedule)
                        <div class="day-row {{ $schedule->day_of_week === now()->dayOfWeekIso ? 'today' : '' }}">
                            <span>{{ $schedule->dayLabel() }}</span>
                            <span>{{ $schedule->timeRange() }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Aucun jour de passage n'est encore programmé.</p>
                    @endforelse

                    <button type="button" class="btn btn-light w-100 mt-3" data-bs-toggle="modal" data-bs-target="#complaint-{{ $truck->id }}">
                        <i class="bi bi-exclamation-triangle me-1"></i> Signaler un problème avec ce camion
                    </button>
                </div>
            </div>

            <div class="modal fade" id="complaint-{{ $truck->id }}" tabindex="-1" aria-labelledby="complaint-title-{{ $truck->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('trucks.complaints.store', $truck) }}" class="modal-content border-0 rounded-4">
                        @csrf
                        <div class="modal-header border-0">
                            <h2 class="modal-title h5" id="complaint-title-{{ $truck->id }}">Un problème avec le {{ $truck->name }} ?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body pt-0">
                            @foreach ($reasons as $reason)
                                <div class="form-check border rounded-3 p-3 ps-5 mb-2">
                                    <input class="form-check-input" type="radio" name="reason" value="{{ $reason->value }}" id="r-{{ $truck->id }}-{{ $reason->value }}" @checked($loop->first)>
                                    <label class="form-check-label w-100" for="r-{{ $truck->id }}-{{ $reason->value }}">{{ $reason->getLabel() }}</label>
                                </div>
                            @endforeach
                            <textarea name="message" rows="2" maxlength="500" class="form-control mt-3" placeholder="Précisez si besoin (jour, heure…)"></textarea>
                        </div>
                        <div class="modal-footer border-0">
                            <button class="btn btn-primary w-100">Envoyer à la mairie</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty"><i class="bi bi-truck"></i>Aucun camion n'est encore affecté à {{ $village->name }}.</div>
        @endforelse
    @endif
</div>
@endsection
