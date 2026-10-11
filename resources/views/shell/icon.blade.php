@php
    $paths = [
        'entries' => '<path d="M4 6h16M4 12h16M4 18h10" />',
        'walks' => '<path d="M4 19c4-1 3-6 7-7s4-6 9-7" /><circle cx="4" cy="19" r="1.5" /><circle cx="20" cy="5" r="1.5" />',
        'map' => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2zM9 4v14M15 6v14" />',
        'sync' => '<path d="M20 12a8 8 0 0 1-14.3 4.9M4 12a8 8 0 0 1 14.3-4.9M18 3v4h-4M6 21v-4h4" />',
        'new' => '<path d="M12 5v14M5 12h14" />',
        'peak' => '<path d="M2 20 9 7l4 7 3-4 6 10z" />',
        'spinner' => '<path d="M21 12a9 9 0 1 1-6.2-8.6" />',
        'back' => '<path d="m15 18-6-6 6-6" />',
    ];
@endphp
<svg viewBox="0 0 24 24" class="{{ $class ?? 'size-6' }}" fill="none" stroke="currentColor" stroke-width="{{ $stroke ?? 2 }}"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] !!}</svg>
