@extends('layouts.app')

@php
    $staffRole = \App\Enums\StaffRole::tryFrom((string) auth()->user()->role);
    $icons = [
        'staff.dashboard' => 'grid', 'officer.exams.index' => 'exams', 'officer.categories.index' => 'layers',
        'officer.applicants.index' => 'user-plus', 'officer.intakes.index' => 'calendar', 'bank.questions.index' => 'help',
        'bank.topics.index' => 'hash', 'invigilator.sittings.index' => 'user-check', 'results.index' => 'chart',
        'admin.staff.index' => 'users',
    ];
@endphp

@section('body')
<div class="min-h-screen lg:pl-64">
    {{-- Mobile top bar --}}
    <div class="sticky top-0 z-30 flex items-center justify-between bg-blue-900 px-4 py-3 text-white lg:hidden">
        <x-brand :sub="false" class="text-white" />
        <button type="button" id="rail-toggle" aria-expanded="false" aria-controls="rail" class="rounded-lg p-2 hover:bg-white/10">
            <x-icon name="menu" /><span class="sr-only">Menu</span>
        </button>
    </div>

    <aside id="rail" class="fixed inset-y-0 left-0 z-20 hidden w-64 flex-col bg-blue-900 pt-16 text-white lg:flex lg:pt-0 max-lg:w-full max-lg:max-w-xs [&.open]:flex">
        <div class="hidden px-6 pb-6 pt-7 lg:block"><x-brand :dark="true" class="text-white" /></div>

        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-2" aria-label="Main">
            @foreach ($staffRole?->navLinks() ?? [] as [$label, $route, $pattern])
                @php $active = request()->routeIs($pattern); @endphp
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                   class="relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold {{ $active ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                    @if ($active)<span class="absolute inset-y-2 left-0 w-1 rounded-full bg-marker-400"></span>@endif
                    <x-icon :name="$icons[$route] ?? 'grid'" class="h-5 w-5 shrink-0 {{ $active ? 'text-marker-400' : 'text-blue-300' }}" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="truncate text-sm font-semibold">{{ auth()->user()->name }}</div>
            <div class="text-xs text-blue-200">{{ $staffRole?->label() }}</div>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('account.password.edit') }}" class="flex items-center gap-1.5 rounded-lg border border-white/25 px-3 py-1.5 text-xs font-semibold hover:bg-white/10"><x-icon name="key" class="h-4 w-4" />Password</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 rounded-lg border border-white/25 px-3 py-1.5 text-xs font-semibold hover:bg-white/10"><x-icon name="logout" class="h-4 w-4" />Sign out</button>
                </form>
            </div>
        </div>
    </aside>

    <main id="main" class="mx-auto max-w-6xl px-5 py-8 pb-14 lg:px-10">
        @if (session('status'))
            <div class="mb-5 flex items-start gap-2 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200" role="alert">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    const rail = document.getElementById('rail'), toggle = document.getElementById('rail-toggle');
    toggle.addEventListener('click', () => {
        const open = rail.classList.toggle('open');
        rail.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', open);
    });
</script>
@endsection
