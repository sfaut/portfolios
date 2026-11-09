<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="tracking-wide scheme-light dark:scheme-dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Applies the theme chosen in the user menu before the first paint (resources/js/theme.js). --}}
        <script>
            try {
                const theme = localStorage.getItem('theme');

                if (theme === 'light' || theme === 'dark') {
                    document.documentElement.classList.add(theme);
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
