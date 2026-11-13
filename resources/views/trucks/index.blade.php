@extends('layouts.app')

@section('title', 'Camion')

@section('content')
<div class="hero">
    <div class="inner">
        <h1>Passage du camion</h1>
        <p class="mb-3">Choisissez votre village pour voir quand passe le camion de la mairie.</p>
        <nav class="chips" aria-label="Villages">
            @foreach ($villages as $v)
                <a href="{{ route('trucks.index', ['village' => $v->slug]) }}" class="chip {{ $village?->id === $v->id ? 'active' : '' }}" @if($village?->id === $v->id) aria-current="true" @endif>{{ $v->name }}</a>
            @endforeach
        </nav>
    </div>
</div>

<div class="sheet">
    <div class="inner">
        @include('partials.flash')

        @if (! $village)
            <div class="empty"><span class="icon-chip"><i class="bi bi-hand-index-thumb"></i></span><strong>Choisissez votre village</strong>Nous le retiendrons pour la prochaine fois.</div>
        @elseif ($trucks->isEmpty())
            <div class="empty"><span class="icon-chip"><i class="bi bi-truck"></i></span><strong>Aucun camion n'est encore affecté à {{ $village->name }}</strong>La mairie organise les tournées village par village.</div>
        @else
            @if ($next)
                @php($date = $next->nextOccurrence())
                <div class="next-passage mb-3">
                    <div class="fw-semibold">Prochain passage à {{ $village->name }}</div>
                    <div class="fs-3 fw-bold lh-sm mt-1">{{ $date->isToday() ? 'Aujourd\'hui' : ($date->isTomorrow() ? 'Demain' : ucfirst($date->translatedFormat('l j F'))) }}</div>
                    <div class="fs-5 fw-semibold">{{ $next->timeRange() }} · {{ $next->truck->name }}</div>
                </div>
            @endif

            @foreach ($trucks as $truck)
                @foreach ($truck->notices as $notice)
                    <div class="callout mb-3">
                        <i class="bi {{ $notice->kind->icon() }}"></i>
                        <div><strong>{{ $truck->name }} · {{ $notice->kind->label() }}</strong>
                            @if ($notice->message)<div>{{ $notice->message }}</div>@endif
                            <div class="small mt-1" style="opacity:.75">{{ $notice->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @endforeach

                <div class="panel mb-3">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="icon-chip" style="background: {{ $truck->color }}; color: #fff"><i class="bi bi-truck"></i></span>
                        <div class="flex-grow-1"><div class="fw-bold">{{ $truck->name }}</div><div class="row-sub">{{ $truck->plate }}</div></div>
                    </div>
                    @foreach ($truck->schedules as $schedule)
                        <div class="row-item py-2">
                            <span class="flex-grow-1 {{ $schedule->day_of_week === now()->dayOfWeekIso ? 'fw-bold text-primary' : '' }}">{{ $schedule->dayLabel() }}</span>
                            <span class="fw-semibold" style="font-variant-numeric: tabular-nums">{{ $schedule->timeRange() }}</span>
                        </div>
                    @endforeach
                    <button type="button" class="btn btn-ghost w-100 mt-2" data-bs-toggle="collapse" data-bs-target="#complaint-{{ $truck->id }}" aria-expanded="false">
                        <i class="bi bi-flag me-1"></i> Signaler un problème avec ce camion
                    </button>
                    <form method="POST" action="{{ route('trucks.complaints.store', $truck) }}" class="collapse mt-3" id="complaint-{{ $truck->id }}">
                        @csrf
                        <div class="d-grid gap-2 mb-2">
                            @foreach ($reasons as $reason)
                                <div class="kind-option">
                                    <input type="radio" name="reason" value="{{ $reason->value }}" id="r-{{ $truck->id }}-{{ $reason->value }}" @checked($loop->first)>
                                    <label for="r-{{ $truck->id }}-{{ $reason->value }}">{{ $reason->getLabel() }}</label>
                                </div>
                            @endforeach
                        </div>
                        <textarea name="message" rows="2" maxlength="500" class="form-control mb-2" placeholder="Précisez si besoin (jour, heure, rue…)"></textarea>
                        <button class="btn btn-primary w-100">Envoyer à la mairie</button>
                    </form>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('.chip.active')?.scrollIntoView({ inline: 'center', block: 'nearest' });
</script>
@endpush
