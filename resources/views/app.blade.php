<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        {{-- In the installed or Android app: a splash while loading, and no pinch-zoom, like a native app.
             The website keeps zoom for accessibility. --}}
        <script>
            (function() {
                var ua = navigator.userAgent || '';
                var app = /\bwv\b|RasoiApp/.test(ua) || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
                if (!app) return;
                document.documentElement.classList.add('in-app');
                window.__splashAt = Date.now();
                var vp = document.querySelector('meta[name=viewport]');
                if (vp) vp.setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover');
            })();
        </script>

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#2E6A3E">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <div id="splash" aria-hidden="true">
            <svg viewBox="0 0 100 100" width="84" height="84"><g fill="none" stroke="#fff" stroke-linecap="round"><circle cx="50" cy="50" r="30" stroke-width="7"/><circle cx="50" cy="50" r="14" stroke-width="7"/></g></svg>
            <span>Rasoi</span>
        </div>
        <x-inertia::app />
    </body>
</html>
