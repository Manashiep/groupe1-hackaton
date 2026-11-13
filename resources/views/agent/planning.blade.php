@extends('layouts.app')

@section('title', 'Planning')

@section('content')
<div class="page">
    <h1 class="mb-3">Planning</h1>

    @if (! $truck)
        <div class="empty"><i class="bi bi-truck"></i>Vous n'êtes rattaché à aucun camion.</div>
    @else
        @foreach ($days as $day)
            <div class="item {{ $day['date']->isToday() ? 'border-primary' : '' }}">
                <div class="text-center flex-shrink-0" style="width: 48px">
                    <div class="small text-muted text-uppercase fw-bold">{{ $day['date']->translatedFormat('D') }}</div>
                    <div class="fs-4 fw-bold lh-1">{{ $day['date']->format('d') }}</div>
                </div>
                <div class="flex-grow-1">
                    @if ($day['schedule'])
                        <div class="fw-bold"><i class="bi bi-truck text-primary me-1"></i>Tournée {{ $day['schedule']->timeRange() }}</div>
                    @else
                        <div class="text-muted">Pas de tournée</div>
                    @endif
                    @foreach ($day['missions'] as $mission)
                        <div class="small mt-1"><span class="fw-bold">{{ $mission['time']->format('H\hi') }}</span> · {{ $mission['label'] }}</div>
                    @endforeach
                </div>
                @if ($day['date']->isToday())
                    <a href="{{ route('agent.route') }}" class="btn btn-primary btn-sm align-self-center">Ouvrir</a>
                @endif
            </div>
        @endforeach
    @endif
</div>
@endsection
