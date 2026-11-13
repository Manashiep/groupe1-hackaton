@php($u = auth()->user())
<div class="dropdown">
    <button class="btn btn-link text-body text-decoration-none d-flex align-items-center gap-2 p-1" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="avatar">{{ $u->initials() }}</span>
        <span class="d-none d-md-block text-start lh-sm">
            <span class="d-block fw-semibold small">{{ $u->name }}</span>
            <span class="d-block text-muted" style="font-size:.72rem">{{ $u->role->label() }}@if($u->isCitizen()) · {{ $u->points }} pts @endif</span>
        </span>
        <i class="bi bi-chevron-down small text-muted d-none d-md-inline"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow">
        <li class="px-3 py-2 d-md-none"><strong class="small">{{ $u->name }}</strong><br><span class="text-muted small">{{ $u->role->label() }}</span></li>
        <li><a class="dropdown-item" href="{{ $u->homeUrl() }}"><i class="bi bi-grid me-2"></i>Mon espace</a></li>
        @if ($u->isStaff())
            <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-globe2 me-2"></i>Site public</a></li>
        @endif
        <li><a class="dropdown-item" href="{{ route('notifications.index') }}"><i class="bi bi-bell me-2"></i>Notifications</a></li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</button>
            </form>
        </li>
    </ul>
</div>
