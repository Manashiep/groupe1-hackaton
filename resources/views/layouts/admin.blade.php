<!doctype html>
<html lang="fr">
<head>
    @include('partials.head')
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">@include('partials.admin-sidebar')</aside>

    <div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="mobileNav" aria-label="Menu">
        <div class="sidebar">@include('partials.admin-sidebar')</div>
    </div>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-2 px-3 px-lg-4 py-2">
                <button class="btn btn-link text-body d-lg-none p-1" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Ouvrir le menu">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div class="me-auto" style="min-width:0">
                    <div class="fw-bold text-truncate">@yield('title', 'Tableau de bord')</div>
                    <div class="text-muted small d-none d-sm-block">{{ ucfirst(now()->translatedFormat('l j F Y')) }}</div>
                </div>
                @include('partials.notifications')
                @include('partials.user-menu')
            </div>
        </header>

        <main class="admin-content">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
