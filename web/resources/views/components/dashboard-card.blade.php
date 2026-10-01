@props(['label', 'value', 'detail'])

<article class="rounded-2xl border border-white/10 bg-slate-900/70 p-5">
    <p class="text-sm text-slate-400">{{ $label }}</p>
    <p class="mt-3 text-2xl font-semibold text-white">{{ $value }}</p>
    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $detail }}</p>
</article>
