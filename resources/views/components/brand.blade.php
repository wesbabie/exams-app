@props(['dark' => false, 'sub' => true])
{{-- ExamSphere emblem plus wordmark. On dark surfaces the emblem sits in a white tile so it stays readable. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    @if ($dark)
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white shadow-sm"><img src="{{ asset('brand/examsphere-mark.png') }}" alt="" width="280" height="217" class="h-7 w-auto"></span>
    @else
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl dark:bg-white"><img src="{{ asset('brand/examsphere-mark.png') }}" alt="" width="280" height="217" class="h-8 w-auto"></span>
    @endif
    <span class="leading-none">
        <span class="font-display block text-[1.2rem] font-extrabold tracking-tight">Exam<span class="{{ $dark ? 'text-blue-300' : 'text-blue-600 dark:text-blue-300' }}">Sphere</span></span>
        @if ($sub)<span class="mt-1 block text-xs {{ $dark ? 'text-blue-200' : 'text-slate-600 dark:text-slate-300' }}">Every Exam. One Platform.</span>@endif
    </span>
</span>
