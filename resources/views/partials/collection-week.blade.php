{{-- Semaine type de collecte : $schedules (CollectionSchedule) --}}
@php($byDay = $schedules->groupBy('day_of_week'))
<div class="week-wrap"><div class="week-grid">
    @foreach (\App\Models\CollectionSchedule::DAYS as $num => $day)
        @php($slots = $byDay->get($num, collect()))
        <div class="week-day {{ $slots->isEmpty() ? 'off' : '' }} {{ now()->dayOfWeekIso === $num ? 'border-warning' : '' }}">
            <div class="name">{{ $day }} @if (now()->dayOfWeekIso === $num)<span class="text-warning">•</span>@endif</div>
            <div class="slots">
                @forelse ($slots as $slot)
                    <div class="slot {{ $slot->waste_type->value }}">
                        <i class="bi {{ $slot->waste_type->icon() }}"></i> {{ $slot->timeRange() }}
                        <div class="fw-normal opacity-75">{{ $slot->waste_type->label() }}</div>
                    </div>
                @empty
                    <div class="small mt-1">Pas de passage</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div></div>
