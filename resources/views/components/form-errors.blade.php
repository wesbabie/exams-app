@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
        <b>Please fix the following:</b>
        <ul class="mt-1 list-disc pl-5">
            @foreach (collect($errors->all())->unique()->take(6) as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    </div>
@endif
