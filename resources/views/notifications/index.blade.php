@extends(auth()->user()->isStaff() ? 'layouts.admin' : 'layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="{{ auth()->user()->isStaff() ? '' : 'page-head' }} d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Notifications</h1>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-soft btn-sm"><i class="bi bi-check2-all me-1"></i> Tout marquer comme lu</button>
    </form>
</div>
<div class="card">
    @forelse ($notifications as $notification)
        <a href="{{ route('notifications.open', $notification->id) }}" class="notif-item {{ $notification->read_at ? '' : 'unread' }}">
            <span class="notif-icon bg-{{ $notification->data['color'] ?? 'primary' }}-subtle text-{{ $notification->data['color'] ?? 'primary' }}-emphasis">
                <i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i>
            </span>
            <span class="flex-grow-1">
                <strong>{{ $notification->data['title'] ?? '' }}</strong>
                <span class="d-block text-muted small">{{ $notification->data['body'] ?? '' }}</span>
            </span>
            <span class="small text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
        </a>
    @empty
        <div class="empty"><i class="bi bi-bell-slash"></i>Aucune notification.</div>
    @endforelse
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
