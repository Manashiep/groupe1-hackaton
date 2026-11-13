@foreach (['success' => ['success', 'bi-check-circle-fill'], 'error' => ['danger', 'bi-exclamation-triangle-fill']] as $key => [$color, $icon])
    @if (session($key))
        <div class="alert alert-{{ $color }} alert-dismissible fade show d-flex gap-2 align-items-start shadow-sm" role="alert">
            <i class="bi {{ $icon }} mt-1"></i>
            <div>{{ session($key) }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif
@endforeach
@if ($errors->any() && ! ($hideErrorSummary ?? false))
    <div class="alert alert-danger d-flex gap-2 align-items-start shadow-sm" role="alert">
        <i class="bi bi-exclamation-octagon-fill mt-1"></i>
        <div>
            <strong>Certaines informations sont à corriger :</strong>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
