<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Baliuag University - Faculty Reclassification</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-bu-muted">
        <div class="min-h-screen flex items-center justify-center px-4 py-10">
            <div class="w-full max-w-lg">
                <div class="text-center mb-6">
                    <a href="{{ url('/') }}" class="inline-flex flex-col items-center gap-3">
                        <img src="{{ asset('images/bu-logo.png') }}"
                             alt="Baliuag University"
                             class="h-28 w-28 object-contain">
                        <div>
                            <div class="text-xs tracking-wide uppercase text-gray-500">Baliuag University</div>
                            <h1 class="text-2xl font-semibold text-bu">Faculty Reclassification</h1>
                        </div>
                    </a>
                </div>

                <div class="w-full px-6 py-6 bg-white shadow-card border border-gray-200 rounded-2xl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
