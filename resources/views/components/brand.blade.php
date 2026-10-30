@props(['dark' => false, 'sub' => true])
{{-- Three answer bubbles, one marked: the system's mark --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <svg viewBox="0 0 44 16" class="h-4 w-auto shrink-0" aria-hidden="true">
        <circle cx="8" cy="8" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".55"/>
        <circle cx="22" cy="8" r="6.75" fill="#f2c230"/>
        <circle cx="36" cy="8" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".55"/>
    </svg>
    <span class="leading-none">
        <span class="font-display block text-[1.05rem] font-bold tracking-tight">ExamSphere</span>
        @if ($sub)<span class="mt-1 block text-xs {{ $dark ? 'text-blue-200' : 'text-slate-500 dark:text-slate-400' }}">Computer-based exams</span>@endif
    </span>
</span>
