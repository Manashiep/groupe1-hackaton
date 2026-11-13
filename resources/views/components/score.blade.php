@props(['data', 'size' => 'bar'])
{{-- Indice de propreté : anneau (size="ring") ou barre compacte (size="bar"). --}}
@if ($size === 'ring')
    <div class="score-ring" style="--val: {{ $data['score'] }}; --col: {{ $data['hex'] }}" role="img" aria-label="Indice de propreté {{ $data['score'] }} sur 100, {{ $data['label'] }}">
        <div><span><b>{{ $data['score'] }}</b><br><small>/ 100</small></span></div>
    </div>
@else
    <div class="d-flex align-items-center gap-2" {{ $attributes }}>
        <div class="score-bar flex-grow-1" role="img" aria-label="Indice {{ $data['score'] }} sur 100">
            <span style="width: {{ $data['score'] }}%; background: {{ $data['hex'] }}"></span>
        </div>
        <span class="fw-bold tabular small" style="min-width: 1.8rem">{{ $data['score'] }}</span>
        <span class="status bg-{{ $data['color'] }}-subtle text-{{ $data['color'] }}-emphasis">{{ $data['label'] }}</span>
    </div>
@endif
