@props(['name', 'class' => 'h-5 w-5'])

@php($attributes = $attributes->merge(['class' => $class, 'fill' => 'none', 'viewBox' => '0 0 24 24', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']))

<svg {{ $attributes }}>
    @switch($name)
        @case('dashboard')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" />
            @break
        @case('users')
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m-2-11.96a4 4 0 0 1 0 7.75" />
            @break
        @case('chart')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9m6 10V5m6 14v-7m4 7H2" />
            @break
        @case('book')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5v14Zm0 0A2.5 2.5 0 0 0 6.5 22H20v-5" />
            @break
        @case('sparkles')
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 3-1.2 3.2L8 7.5l2.8 1.3L12 12l1.2-3.2L16 7.5l-2.8-1.3L12 3ZM5 14l-.8 2.2L2 17l2.2.8L5 20l.8-2.2L8 17l-2.2-.8L5 14Zm13-1-1 2.7-2.5 1.1 2.5 1.1 1 2.7 1-2.7 2.5-1.1-2.5-1.1L18 13Z" />
            @break
        @case('calendar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 2v4m12-4v4M3 9h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z" />
            @break
        @case('bot')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v3m-7 5h14a2 2 0 0 1 2 2v7H3v-7a2 2 0 0 1 2-2Zm2 5h.01M17 15h.01M7 19v3m10-3v3M8 10V7h8v3" />
            @break
        @case('quiz')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 11a3 3 0 1 1 3 3v2m0 4h.01M4 2h16v20H4V2Z" />
            @break
        @case('trend')
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 17 6-6 4 4 8-9m-5 0h5v5" />
            @break
        @case('menu')
            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('close')
            <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
            @break
        @case('bell')
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Zm-8 13h4" />
            @break
        @case('logout')
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5m5 5H3m11-9h6v18h-6" />
            @break
        @case('arrow')
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
            @break
        @case('check')
            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
