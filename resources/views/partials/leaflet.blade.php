@once
    @push('head')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    @endpush
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script src="{{ asset('js/maps.js') }}?v=5"></script>
    @endpush
@endonce
