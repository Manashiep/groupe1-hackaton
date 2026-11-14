<!doctype html>
<html lang="fr">
<head>
    @include('partials.head')
</head>
<body>
    <div class="guest-theme">@include('partials.theme-toggle')</div>
    @yield('content')
    @stack('scripts')
</body>
</html>
