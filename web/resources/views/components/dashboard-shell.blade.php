@props(['eyebrow', 'title', 'description'])

<div class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
    <section class="overflow-hidden rounded-3xl border border-white/10 bg-white/[0.05] p-7 shadow-2xl shadow-cyan-950/20 sm:p-10">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-300">{{ $eyebrow }}</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">{{ $title }}</h1>
        <p class="mt-4 max-w-2xl leading-7 text-slate-400">{{ $description }}</p>

        <div class="mt-10 grid gap-4 md:grid-cols-3">
            {{ $slot }}
        </div>
    </section>
</div>
