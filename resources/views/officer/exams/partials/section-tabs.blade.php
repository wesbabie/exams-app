<nav class="mb-5 flex flex-wrap gap-2 text-sm" aria-label="Sections">
    @foreach ($exam->sections as $s)
        @php $st = $statuses[$s->id] ?? ['have' => 0, 'need' => $s->question_count, 'ok' => false]; @endphp
        <a href="{{ route('officer.exams.sections.questions.edit', [$exam, $s]) }}"
           class="rounded-full border px-3 py-1 font-semibold {{ $s->id === $section->id ? 'border-blue-700 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-slate-700 dark:text-blue-400' : 'border-slate-300 text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700' }}">
            {{ $s->position }}. {{ $s->title }}
            <span class="{{ $st['ok'] ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $st['have'] }}/{{ $st['need'] }}{{ $st['ok'] ? ' ✓' : '' }}</span>
        </a>
    @endforeach
</nav>
