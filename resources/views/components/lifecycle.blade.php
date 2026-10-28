@props(['status'])
@php
    $steps = ['draft' => 'Draft', 'in_review' => 'In review', 'approved' => 'Approved', 'scheduled' => 'Scheduled', 'closed' => 'Closed'];
    $keys = array_keys($steps);
    $at = array_search($status, $keys, true);
    $at = $at === false ? 0 : $at;
@endphp
<ol class="mb-6 grid grid-cols-5 gap-1.5" aria-label="Exam stage">
    @foreach ($steps as $key => $label)
        @php $i = array_search($key, $keys, true); @endphp
        <li class="min-w-0" @if ($i === $at) aria-current="step" @endif>
            <div class="h-1.5 rounded-full {{ $i < $at ? 'bg-blue-700' : ($i === $at ? 'bg-marker-400' : 'bg-slate-300 dark:bg-slate-600') }}"></div>
            <p class="mt-1.5 truncate text-xs {{ $i === $at ? 'font-bold' : 'text-slate-600 dark:text-slate-300' }}">{{ $label }}</p>
        </li>
    @endforeach
</ol>
