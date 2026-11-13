@extends('layouts.app')

@section('title', 'Planning')

@section('content')
<div class="hero">
    <div class="inner d-flex justify-content-between align-items-start gap-3">
        <div>
            <h1>Planning</h1>
            <p class="mb-0">{{ $truck?->name ?? 'Aucun camion' }} · 7 prochains jours</p>
        </div>
        @include('agent._tabs')
    </div>
</div>

<div class="sheet">
    <div class="inner">
        @if (! $truck)
            <div class="empty"><span class="icon-chip"><i class="bi bi-truck"></i></span><strong>Aucun camion ne vous est affecté</strong></div>
        @else
            <div class="panel py-1">
                @foreach ($days as $day)
                    <div class="row-item align-items-start">
                        <div class="text-center flex-shrink-0" style="width: 48px">
                            <div class="small text-uppercase fw-bold {{ $day['date']->isToday() ? 'text-primary' : 'text-muted' }}">{{ $day['date']->translatedFormat('D') }}</div>
                            <div class="fs-4 fw-bold lh-1">{{ $day['date']->format('d') }}</div>
                        </div>
                        <div class="flex-grow-1">
                            @forelse ($day['slots'] as $slot)
                                <div class="d-flex justify-content-between gap-2 py-1">
                                    <span class="fw-bold">{{ $slot->village->name }}</span>
                                    <span style="font-variant-numeric: tabular-nums">{{ $slot->timeRange() }}</span>
                                </div>
                            @empty
                                <div class="text-muted py-1">Repos</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
