<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0f766e">
<title>@hasSection('title')@yield('title') · @endif CleanComores</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%230f766e'/><path d='M10 12h12l-1.2 12.2a2 2 0 0 1-2 1.8h-5.6a2 2 0 0 1-2-1.8z' fill='white'/><rect x='8.5' y='8.5' width='15' height='2.4' rx='1.2' fill='%23f5b301'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
@stack('head')
<link href="{{ asset('css/app.css') }}?v=2" rel="stylesheet">
