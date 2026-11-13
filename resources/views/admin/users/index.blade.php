@extends('layouts.admin')

@section('title', 'Comptes utilisateurs')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills gap-1">
        <li class="nav-item"><a href="{{ route('admin.users.index', array_filter(['q' => $filters['q'] ?? null])) }}" class="nav-link {{ empty($filters['role']) ? 'active' : '' }}">Tous <span class="badge text-bg-light ms-1">{{ $counts->sum() }}</span></a></li>
        @foreach ($roles as $role)
            <li class="nav-item"><a href="{{ route('admin.users.index', array_filter(['role' => $role->value, 'q' => $filters['q'] ?? null])) }}" class="nav-link {{ ($filters['role'] ?? '') === $role->value ? 'active' : '' }}">{{ $role->label() }} <span class="badge text-bg-light ms-1">{{ $counts[$role->value] ?? 0 }}</span></a></li>
        @endforeach
    </ul>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
            @if (! empty($filters['role']))<input type="hidden" name="role" value="{{ $filters['role'] }}">@endif
            <input name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Nom, e-mail, téléphone" aria-label="Rechercher">
        </form>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary text-nowrap"><i class="bi bi-person-plus me-1"></i> Créer un compte</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Rattachement</th><th class="text-end">Points</th><th>État</th><th></th></tr></thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="{{ $user->is_active ? '' : 'opacity-50' }}">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar">{{ $user->initials() }}</span>
                                <div><div class="fw-semibold">{{ $user->name }}</div><div class="small text-muted">{{ $user->email }} @if($user->phone) · {{ $user->phone }} @endif</div></div>
                            </div>
                        </td>
                        <td><span class="status bg-{{ $user->role->color() }}-subtle text-{{ $user->role->color() }}-emphasis">{{ $user->role->label() }}</span></td>
                        <td class="small">{{ $user->team?->name ?? $user->quartier?->name ?? '—' }}</td>
                        <td class="text-end tabular">{{ $user->isCitizen() ? $user->points : '' }}</td>
                        <td class="small">{!! $user->is_active ? '<span class="text-success"><i class="bi bi-circle-fill" style="font-size:.5rem"></i> Actif</span>' : '<span class="text-muted">Désactivé</span>' !!}</td>
                        <td class="text-end"><a href="{{ route('admin.users.edit', $user) }}" class="btn btn-soft btn-sm">Modifier</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
