<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="tracking-wide scheme-light-dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVQI12P4//8/AAX+Av7czFnnAAAAAElFTkSuQmCC">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=sarabun:200,300,400,500,600,700,800,900,400i">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(["resources/css/app.css", "resources/js/app.js"])
    </head>
    <body class="font-sans font-normal text-gray-700 dark:text-gray-300">
        {{ $slot }}
    </body>
</html>
