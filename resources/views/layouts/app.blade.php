<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#073a27">
    <title>@yield('title', 'Exams') · ExamSphere</title>
    <link rel="icon" href="{{ url('/favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-100 text-slate-800 antialiased dark:bg-slate-900 dark:text-slate-100">
    <a href="#main" class="sr-only rounded-lg bg-white px-4 py-2 font-semibold text-blue-700 focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50">Skip to content</a>
    @yield('body')
</body>
</html>
