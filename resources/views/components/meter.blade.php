@props(['value', 'max', 'tone' => null, 'label' => null])
@php $pct = $max > 0 ? min(100, max(0, round($value / $max * 100))) : 0; @endphp
<div class="meter" role="meter" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="{{ $max }}" @if ($label) aria-label="{{ $label }}" @endif>
    <span class="{{ $tone ? 'is-'.$tone : '' }}" style="width: {{ $pct }}%"></span>
</div>
