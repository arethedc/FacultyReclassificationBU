<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Baliuag University - Faculty Reclassification</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bu-muted text-gray-900 antialiased">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <header class="flex items-center justify-end gap-3">
            @auth
                <a href="{{ url('/dashboard') }}"
                   class="px-4 py-2 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark">
                    Dashboard
                </a>
            @else
         
            @endauth
        </header>

        <main class="mt-8 bg-white border border-gray-200 rounded-2xl shadow-card p-8 sm:p-10 lg:p-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold border border-green-200">
                        Baliuag University
                    </div>
                    <h1 class="mt-4 text-3xl sm:text-4xl font-semibold text-gray-800 leading-tight">
                        Faculty Reclassification System
                    </h1>
                    <p class="mt-4 text-base text-gray-600 max-w-xl">
                        Manage faculty reclassification submissions, evidence, and review workflow for Dean, HR, VPAA, and President.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="px-5 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark">
                                Open Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-5 py-2.5 rounded-xl bg-bu text-white text-sm font-semibold shadow-soft hover:bg-bu-dark">
                                Sign in
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="flex justify-center lg:justify-end">
                    <div class="bg-white border border-green-100 rounded-2xl p-6 w-full max-w-md">
                        <div class="flex items-center gap-4">
                            <img src="{{ asset('images/bu-logo.png') }}" alt="Baliuag University Logo" class="h-20 w-20 object-contain">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-gray-500">Official Portal</div>
                                <div class="text-lg font-semibold text-bu">Faculty Reclassification</div>
                            </div>
                        </div>
                        <div class="mt-5 text-sm text-gray-600 space-y-2">
                            <p>- Role-based workflow and approval routing</p>
                            <p>- Evidence library and criterion mapping</p>
                            <p>- Submission tracking and period management</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
