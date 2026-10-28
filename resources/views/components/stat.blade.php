@props(['label', 'value', 'sub' => null])
<div {{ $attributes->merge(['class' => 'panel !p-4']) }}>
    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $label }}</p>
    <p class="font-display mt-1 text-3xl font-bold leading-none tabular-nums">{{ $value }}@if ($sub) <span class="text-base font-semibold text-slate-500 dark:text-slate-400">{{ $sub }}</span>@endif</p>
    {{ $slot }}
</div>
