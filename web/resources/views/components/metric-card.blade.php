@props(['label', 'value', 'suffix' => null, 'detail', 'icon' => 'chart', 'tone' => 'cyan'])

@php
    $tones = [
        'cyan' => 'bg-cyan-50 text-cyan-600 ring-cyan-100',
        'indigo' => 'bg-indigo-50 text-indigo-600 ring-indigo-100',
        'amber' => 'bg-amber-50 text-amber-600 ring-amber-100',
        'rose' => 'bg-rose-50 text-rose-600 ring-rose-100',
        'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
    ];
@endphp

<article class="dashboard-panel p-5">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                {{ $value }}<span class="text-lg text-slate-400">{{ $suffix }}</span>
            </p>
        </div>
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl ring-1 {{ $tones[$tone] ?? $tones['cyan'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
    <p class="mt-4 text-xs leading-5 text-slate-400">{{ $detail }}</p>
</article>
