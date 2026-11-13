@if (session('success'))
    <div class="flash flash-success" role="status"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="flash flash-error" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    </div>
@endif
