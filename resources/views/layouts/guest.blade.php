<!doctype html>
<html lang="fr">
<head>
    @include('partials.head')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
