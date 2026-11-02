@php
    $show = function ($v) {
        if ($v === null || $v === '') return '(empty)';
        if (is_bool($v)) return $v ? 'yes' : 'no';
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        return \Illuminate\Support\Str::limit((string) $v, 120);
    };
    $nice = fn ($k) => str_replace('_', ' ', $k);
@endphp
<ul class="space-y-2 text-sm">
    @foreach ($changes as $c)
        <li>
            <b>{{ $c['model'] }}</b> <span class="text-slate-600 dark:text-slate-300">{{ $c['label'] ?? '' }}</span>
            @if (! empty($c['created'])) <span class="chip chip-good ml-1">Created</span>
            @elseif (! empty($c['deleted'])) <span class="chip chip-bad ml-1">Deleted</span>
            @elseif (! empty($c['changed']))
                <dl class="mt-1 grid grid-cols-[auto_1fr] gap-x-4 gap-y-0.5 pl-3">
                    @foreach ($c['changed'] as $field => $diff)
                        <dt class="text-slate-600 dark:text-slate-300">{{ $nice($field) }}</dt>
                        <dd><s class="text-slate-500">{{ $show($diff['from'] ?? null) }}</s> <span aria-label="changed to">→</span> <b>{{ $show($diff['to'] ?? null) }}</b></dd>
                    @endforeach
                </dl>
            @endif
        </li>
    @endforeach
</ul>
