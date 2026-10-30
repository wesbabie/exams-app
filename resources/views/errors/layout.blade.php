<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#073a27">
    <title>@yield('heading') · ExamSphere</title>
    <link rel="icon" href="{{ url('/favicon.svg') }}" type="image/svg+xml">
    {{-- An error page must never fail because the asset build is missing --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @fonts
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="min-h-full bg-slate-100 text-slate-800 antialiased dark:bg-slate-900 dark:text-slate-100" style="font-family: system-ui, sans-serif">
<main class="omr-field flex min-h-screen items-center justify-center px-5 py-10">
    <div class="w-full max-w-md rounded-2xl bg-white p-7 text-slate-800 shadow-xl dark:bg-slate-800 dark:text-slate-100 sm:p-9">
        <p class="mb-5 inline-flex items-center gap-3 text-blue-800 dark:text-blue-100">
            <svg viewBox="0 0 44 16" class="h-4 w-auto" aria-hidden="true"><circle cx="8" cy="8" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".55"/><circle cx="22" cy="8" r="6.75" fill="#f2c230"/><circle cx="36" cy="8" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".55"/></svg>
            <b class="font-display text-lg">ExamSphere</b>
        </p>
        <p class="font-display text-5xl font-extrabold leading-none tabular-nums text-slate-300 dark:text-slate-600" aria-hidden="true">@yield('code')</p>
        <h1 class="mt-3 text-3xl">@yield('heading')</h1>
        <div class="mt-3 leading-relaxed text-slate-600 dark:text-slate-300">@yield('message')</div>
        <div class="mt-6 flex flex-wrap gap-2">@yield('actions')</div>
    </div>
</main>
</body>
</html>
