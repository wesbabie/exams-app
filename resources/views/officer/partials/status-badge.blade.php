@php
    $tones = ['draft' => 'neutral', 'in_review' => 'warn', 'approved' => 'good', 'scheduled' => 'info', 'closed' => 'muted'];
    $labels = ['draft' => 'Draft', 'in_review' => 'In review', 'approved' => 'Approved', 'scheduled' => 'Scheduled', 'closed' => 'Closed'];
@endphp
<span class="chip chip-{{ $tones[$status] ?? 'neutral' }}">{{ $labels[$status] ?? $status }}</span>
