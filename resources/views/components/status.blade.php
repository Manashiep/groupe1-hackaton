@props(['status'])
@php
    $color = $status->color();
    $icon = method_exists($status, 'icon') ? $status->icon() : null;
@endphp
<span {{ $attributes->merge(['class' => "status bg-{$color}-subtle text-{$color}-emphasis border-{$color}-subtle"]) }}>
    @if ($icon)<i class="bi {{ $icon }}"></i>@endif{{ $status->label() }}
</span>
