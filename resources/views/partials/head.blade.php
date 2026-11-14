<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#1d6b47">
{{-- Thème clair / sombre / automatique, comme dans l'espace mairie. Le choix est gardé sur l'appareil (localStorage)
     et appliqué avant l'affichage de la page, pour éviter un flash blanc en mode sombre. --}}
<script>
    (() => {
        const key = 'theme';
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)');
        const read = () => { try { return localStorage.getItem(key) || 'system'; } catch (e) { return 'system'; } };
        const apply = (choice) => {
            const dark = choice === 'dark' || (choice === 'system' && systemDark.matches);
            document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        };
        window.ccTheme = {
            get: read,
            set(choice) {
                try { choice === 'system' ? localStorage.removeItem(key) : localStorage.setItem(key, choice); } catch (e) {}
                apply(choice);
            },
        };
        apply(read());
        systemDark.addEventListener('change', () => { if (read() === 'system') apply('system'); });

        // Boutons ☀ / ☾ / ◐ de la barre du haut (partials/theme-toggle).
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = document.querySelectorAll('[data-theme-choice]');
            const mark = () => buttons.forEach((button) => {
                const active = button.dataset.themeChoice === read();
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active);
            });
            buttons.forEach((button) => button.addEventListener('click', () => { window.ccTheme.set(button.dataset.themeChoice); mark(); }));
            mark();
        });
    })();
</script>
<title>@hasSection('title')@yield('title') · @endif EcoCom</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%231d6b47'/><path d='M22 9c-7 0-12 4-12 10 0 1.3.3 2.4.8 3.4L9 24.2l1.4 1.4 1.8-1.8c1 .5 2.1.8 3.4.8 6 0 9.4-6 6.4-15.6z' fill='white'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
@stack('head')
<link href="{{ asset('css/app.css') }}?v=8" rel="stylesheet">
