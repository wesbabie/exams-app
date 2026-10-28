@php
    $styles = [
        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        'in_review' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'retired' => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
    ];
@endphp
<span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $styles[$status] ?? $styles['draft'] }}">{{ \App\Models\Question::STATUSES[$status] ?? $status }}</span>
