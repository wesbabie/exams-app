@extends('layouts.app')

@section('body')
<header class="bg-blue-900 text-white">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-5 py-3.5">
        <x-brand :sub="false" class="text-white" />
        <div class="flex items-center gap-3 text-sm">
            <span class="hidden sm:inline">@yield('who')</span>
            <form method="POST" action="@yield('logout_action')">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 rounded-lg border border-white/30 px-3.5 py-2 font-semibold hover:bg-white/10"><x-icon name="logout" class="h-4 w-4" />Sign out</button>
            </form>
        </div>
    </div>
</header>
<main id="main" class="mx-auto max-w-5xl px-5 py-8 pb-14">
    @yield('content')
</main>
@endsection
