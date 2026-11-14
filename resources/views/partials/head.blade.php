<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#1d6b47">
<title>@hasSection('title')@yield('title') · @endif EcoCom</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%231d6b47'/><path d='M22 9c-7 0-12 4-12 10 0 1.3.3 2.4.8 3.4L9 24.2l1.4 1.4 1.8-1.8c1 .5 2.1.8 3.4.8 6 0 9.4-6 6.4-15.6z' fill='white'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
@stack('head')
<link href="{{ asset('css/app.css') }}?v=6" rel="stylesheet">
