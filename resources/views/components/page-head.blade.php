@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'Back'])
<header class="mb-6">
    @if ($back)
        <a href="{{ $back }}" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>{{ $backLabel }}
        </a>
    @endif
    <div class="{{ $back ? 'mt-3' : '' }} flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div class="min-w-0">
            @isset($badge)<div class="mb-2 flex flex-wrap items-center gap-2">{{ $badge }}</div>@endisset
            <h1 class="page-title">{{ $title }}</h1>
            @if ($subtitle)<p class="mt-1.5 max-w-2xl text-slate-600 dark:text-slate-300">{{ $subtitle }}</p>@endif
        </div>
        @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
    </div>
</header>
