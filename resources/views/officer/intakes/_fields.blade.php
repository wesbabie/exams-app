@php
    $input = 'field';
    $fmt = fn ($d) => $d?->format('Y-m-d\TH:i');
@endphp
<label for="name" class="mb-1 mt-3 block text-sm font-semibold">Name</label>
<input id="name" name="name" value="{{ old('name', $intake?->name) }}" required maxlength="255" placeholder="Internship Intake 2027" class="{{ $input }}">

<label for="year" class="mb-1 mt-3 block text-sm font-semibold">Year</label>
<input id="year" name="year" type="number" value="{{ old('year', $intake?->year ?? now()->year) }}" required min="2024" max="2100" class="{{ $input }}">

<label for="opens_at" class="mb-1 mt-3 block text-sm font-semibold">Opens</label>
<input id="opens_at" name="opens_at" type="datetime-local" value="{{ old('opens_at', $fmt($intake?->opens_at)) }}" required class="{{ $input }}">

<label for="closes_at" class="mb-1 mt-3 block text-sm font-semibold">Closes</label>
<input id="closes_at" name="closes_at" type="datetime-local" value="{{ old('closes_at', $fmt($intake?->closes_at)) }}" required class="{{ $input }}">
