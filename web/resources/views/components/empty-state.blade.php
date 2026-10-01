@props(['title', 'message'])

<div class="grid min-h-48 place-items-center rounded-xl border border-dashed border-slate-200 bg-slate-50/70 p-6 text-center">
    <div>
        <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
            <x-icon name="sparkles" class="h-5 w-5" />
        </span>
        <p class="mt-4 text-sm font-semibold text-slate-700">{{ $title }}</p>
        <p class="mt-1 max-w-xs text-xs leading-5 text-slate-400">{{ $message }}</p>
    </div>
</div>
