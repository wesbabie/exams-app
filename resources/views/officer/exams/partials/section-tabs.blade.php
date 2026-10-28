<nav class="mb-5 flex flex-wrap gap-2 text-sm" aria-label="Sections">
    @foreach ($exam->sections as $s)
        @php $st = $statuses[$s->id] ?? ['have' => 0, 'need' => $s->question_count, 'ok' => false]; @endphp
        <a href="{{ route('officer.exams.sections.questions.edit', [$exam, $s]) }}"
           class="pill {{ $s->id === $section->id ? 'pill-on' : '' }}">
            {{ $s->position }}. {{ $s->title }}
            <span class="{{ $st['ok'] ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $st['have'] }}/{{ $st['need'] }}{{ $st['ok'] ? ' ✓' : '' }}</span>
        </a>
    @endforeach
</nav>
