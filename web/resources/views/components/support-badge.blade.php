@props(['level'])

@php
    $styles = match ($level) {
        'HIGH' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'MODERATE' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'LOW' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold tracking-wide ring-1 ring-inset {{ $styles }}">
    {{ $level }}
</span>
