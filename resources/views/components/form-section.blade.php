@props(['title', 'hint' => null])
<section {{ $attributes->merge(['class' => 'panel']) }}>
    <h2 class="font-display text-xl font-bold">{{ $title }}</h2>
    @if ($hint)<p class="mb-4 mt-1 max-w-prose text-sm text-slate-600 dark:text-slate-300">{{ $hint }}</p>@else<div class="mb-4"></div>@endif
    {{ $slot }}
</section>
