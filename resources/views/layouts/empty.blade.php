<!doctype html>
{{--
    Document HTML de toutes les pages. Pour un utilisateur connecté, <html> porte la classe de son thème
    enregistré (App\Enums\ColorScheme), son choix et l'adresse où l'enregistrer, lus par
    resources/js/theme.js.

    @var \Illuminate\View\ComponentSlot $slot Corps de la page.
--}}
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class(['tracking-wide scheme-light dark:scheme-dark', auth()->user()?->color_scheme->htmlClass() ?? ''])
    @auth
        data-color-scheme="{{ auth()->user()->color_scheme->value }}"
        data-color-scheme-url="{{ route('profile.color-scheme') }}"
    @endauth
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{--
            Thème avant le premier rendu (resources/js/theme.js). Connecté, le serveur a posé la classe
            du thème enregistré : le script le recopie dans le navigateur, pour les pages d'invité qui
            suivront. Invité, il applique le dernier thème de ce navigateur.
        --}}
        <script>
            try {
                const root = document.documentElement;
                const saved = root.dataset.colorScheme;

                if (saved === 'light' || saved === 'dark') {
                    localStorage.setItem('theme', saved);
                } else if (saved === 'system') {
                    localStorage.removeItem('theme');
                } else {
                    const theme = localStorage.getItem('theme');

                    if (theme === 'light' || theme === 'dark') {
                        root.classList.add(theme);
                    }
                }
            } catch {
                //
            }
        </script>

        <link rel="icon" href="/favicon.ico" sizes="48x48">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=sarabun:200,300,400,500,600,700,800,900,400i">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(["resources/css/app.css", "resources/js/app.js"])
    </head>
    <body class="font-sans font-normal text-gray-700 dark:text-gray-300">
        {{ $slot }}
    </body>
</html>
