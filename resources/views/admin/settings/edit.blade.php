@extends('layouts.staff')

@section('title', 'System settings')

@php $muted = 'text-slate-600 dark:text-slate-300'; @endphp

@section('content')
<x-page-head title="System settings" subtitle="The rules the whole system follows. Each has a standard value and a range it must stay inside. Every change is recorded in the audit log." />

<x-form-errors />

<form method="POST" action="{{ route('admin.settings.update') }}" id="settings-form">
    @csrf
    @method('PUT')

    <div class="max-w-3xl space-y-6">
        @foreach ($groups as $group)
            <x-form-section :title="$group['title']" :hint="$group['text']">
                <div class="-my-2 divide-y divide-slate-200 dark:divide-slate-700">
                    @foreach ($group['items'] as $item)
                        <div class="grid gap-x-6 gap-y-2 py-4 sm:grid-cols-[1fr_11rem]">
                            <div>
                                <label for="{{ $item['field'] }}" class="font-semibold">{{ $item['label'] }}</label>
                                @if ($item['help'])<p class="text-sm {{ $muted }}">{{ $item['help'] }}</p>@endif
                                <p class="mt-1 text-xs {{ $muted }}">
                                    Standard: {{ $item['default'] }} {{ $item['unit'] }} · allowed {{ $item['min'] }} to {{ $item['max'] }}
                                    @if ($item['changed'] && $item['row'])<span class="chip chip-warn ml-1.5 align-middle">Changed</span> by {{ $item['row']->updatedBy?->name ?? 'someone' }}, {{ $item['row']->updated_at->format('j M Y') }}@endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2 sm:justify-end">
                                <input id="{{ $item['field'] }}" name="{{ $item['field'] }}" type="number" inputmode="numeric" min="{{ $item['min'] }}" max="{{ $item['max'] }}" step="1" required
                                       value="{{ old($item['field'], $item['value']) }}" class="field-sm w-24 text-right tabular-nums">
                                <span class="w-16 text-sm {{ $muted }}">{{ $item['unit'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-form-section>
        @endforeach
    </div>

    <div class="formbar flex flex-wrap items-center gap-3">
        <button type="submit" class="btn btn-primary px-7 py-3">Save settings</button>
        @if ($anyChanged)
            <button type="submit" form="reset-form" class="btn btn-neutral py-3" onclick="return confirm('Put every setting back to its standard value?')">Back to standard values</button>
        @endif
    </div>
</form>

<form method="POST" action="{{ route('admin.settings.reset') }}" id="reset-form">@csrf</form>
@endsection
