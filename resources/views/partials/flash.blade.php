@if (session('success'))
    <div class="alert alert-success d-flex gap-2 align-items-center border-0 rounded-4" role="status">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger border-0 rounded-4" role="alert">
        @foreach ($errors->all() as $error)
            <div><i class="bi bi-exclamation-circle me-1"></i>{{ $error }}</div>
        @endforeach
    </div>
@endif
