@extends('layouts.staff')

@section('title', 'Dashboard')

@php
    // Group the work areas by where they sit in the life of an exam
    $groups = ['Prepare' => [], 'On exam day' => [], 'Afterwards' => [], 'Administration' => [], 'Coming soon' => []];
    foreach ($role->workAreas() as $area) {
        [$title, $text, $route] = $area;
        $key = match (true) {
            ! $route => 'Coming soon',
            str_starts_with($route, 'invigilator.') => 'On exam day',
            str_starts_with($route, 'results.') => 'Afterwards',
            str_starts_with($route, 'admin.') => 'Administration',
            default => 'Prepare',
        };
        $groups[$key][] = $area;
    }
@endphp

@section('content')
<h1 class="page-title">Welcome, {{ $user->name }}</h1>
<p class="mb-8 mt-1.5 text-slate-600 dark:text-slate-300">You are signed in as {{ strtolower($role->label()) }}. Start with the stage you are working on.</p>

<div class="grid items-start gap-6 xl:grid-cols-2">
    @foreach ($groups as $heading => $areas)
        @continue(empty($areas))
        <section class="panel p-0">
            <h2 class="px-5 pb-1 pt-5 text-lg font-bold {{ $heading === 'Coming soon' ? 'text-slate-500 dark:text-slate-400' : '' }}">{{ $heading }}</h2>
            <ul class="pb-2">
                @foreach ($areas as [$title, $text, $route])
                    <li class="border-t border-slate-200 first:border-t-0 dark:border-slate-700">
                        @if ($route)
                            <a href="{{ route($route) }}" class="group flex items-center justify-between gap-4 px-5 py-3.5 hover:bg-blue-50 dark:hover:bg-slate-700">
                                <span>
                                    <span class="block font-semibold group-hover:text-blue-700 dark:group-hover:text-blue-300">{{ $title }}</span>
                                    <span class="block text-sm text-slate-600 dark:text-slate-300">{{ $text }}</span>
                                </span>
                                <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0 text-slate-400 group-hover:text-blue-700" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                            </a>
                        @else
                            <div class="px-5 py-3.5">
                                <span class="block font-semibold text-slate-500 dark:text-slate-400">{{ $title }}</span>
                                <span class="block text-sm text-slate-500 dark:text-slate-400">{{ $text }}</span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
@endsection
