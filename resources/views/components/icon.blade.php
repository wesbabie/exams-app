@props(['name', 'class' => 'h-5 w-5'])
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes->merge(['class' => $class]) }}>
@switch($name)
    @case('grid')
        <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
        @break
    @case('exams')
        <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v3H9z"/><path d="m9 14 2 2 4-4"/>
        @break
    @case('layers')
        <path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>
        @break
    @case('user-plus')
        <circle cx="9" cy="8" r="3.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M18 8v6M15 11h6"/>
        @break
    @case('calendar')
        <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>
        @break
    @case('help')
        <circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1 .9-1 1.7"/><path d="M12 17h.01"/>
        @break
    @case('hash')
        <path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/>
        @break
    @case('user-check')
        <circle cx="9" cy="8" r="3.5"/><path d="M3 20c0-3.3 2.7-6 6-6 1.2 0 2.3.3 3.2 1"/><path d="m15 17 2 2 4-4"/>
        @break
    @case('chart')
        <path d="M5 20v-8M12 20V5M19 20V9"/>
        @break
    @case('users')
        <circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17" cy="9" r="2.5"/><path d="M17.5 14c2.4 0 4 1.7 4 4.5"/>
        @break
    @case('logout')
        <path d="m15 17 5-5-5-5M20 12H9M12 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7"/>
        @break
    @case('menu')
        <path d="M4 7h16M4 12h16M4 17h16"/>
        @break
    @case('key')
        <circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M16 7l3 3"/>
        @break
    @case('check')
        <path d="m5 12.5 4.5 4.5L19 7.5"/>
        @break
@endswitch
</svg>
