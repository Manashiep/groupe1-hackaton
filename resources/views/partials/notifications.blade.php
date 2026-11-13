@php
    $unreadCount = auth()->user()->unreadNotifications()->count();
    $recent = auth()->user()->notifications()->limit(6)->get();
@endphp
<div class="dropdown">
    <button class="btn btn-link text-body bell p-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifications ({{ $unreadCount }} non lues)">
        <i class="bi bi-bell fs-5"></i>
        @if ($unreadCount)
            <span class="dot">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end notif-menu shadow">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <strong>Notifications</strong>
            @if ($unreadCount)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-link btn-sm p-0 text-decoration-none">Tout marquer comme lu</button>
                </form>
            @endif
        </div>
        @forelse ($recent as $notification)
            <a href="{{ route('notifications.open', $notification->id) }}" class="notif-item {{ $notification->read_at ? '' : 'unread' }}">
                <span class="notif-icon bg-{{ $notification->data['color'] ?? 'primary' }}-subtle text-{{ $notification->data['color'] ?? 'primary' }}-emphasis">
                    <i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i>
                </span>
                <span class="small">
                    <strong class="d-block">{{ $notification->data['title'] ?? '' }}</strong>
                    <span class="text-muted">{{ \Illuminate\Support\Str::limit($notification->data['body'] ?? '', 110) }}</span>
                    <span class="d-block text-muted mt-1" style="font-size:.72rem">{{ $notification->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <div class="empty py-4"><i class="bi bi-bell-slash"></i>Aucune notification pour l'instant.</div>
        @endforelse
        <a href="{{ route('notifications.index') }}" class="d-block text-center small fw-semibold py-2 text-decoration-none">Voir tout</a>
    </div>
</div>
