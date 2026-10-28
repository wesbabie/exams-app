@php
    $tones = ['draft' => 'neutral', 'in_review' => 'warn', 'approved' => 'good', 'retired' => 'muted'];
@endphp
<span class="chip chip-{{ $tones[$status] ?? 'neutral' }}">{{ \App\Models\Question::STATUSES[$status] ?? $status }}</span>
