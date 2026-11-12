@props(['name', 'class' => 'size-5'])

@php
    /*
    | A self-contained icon set.
    |
    | Icons are inlined rather than pulled from a CDN so the app works offline,
    | ships no extra request, and inherits `currentColor` — which is what lets
    | every icon follow the active theme automatically.
    */
    $icons = [
        // Navigation
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h3.5v-6h5v6H18a1 1 0 0 0 1-1V9.5"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'wallet' => '<path d="M19 7V5.5A1.5 1.5 0 0 0 17.5 4H5a2 2 0 0 0 0 4h13a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M16.5 12.5h.01"/>',
        'receipt' => '<path d="M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17l-2.5-1.5L14 21l-2-1.5L10 21l-2.5-1.5z"/><path d="M9 7h6M9 11h6M9 15h3"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6 8.5-6"/>',
        'camera' => '<path d="M4 8a2 2 0 0 1 2-2h1.5l1.5-2h6l1.5 2H18a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><circle cx="12" cy="13" r="3.5"/>',
        'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r="1"/>',
        'tag' => '<path d="M20.6 13.6 13 21.2a1.6 1.6 0 0 1-2.3 0l-7.9-8V3.5h9.7l8.1 7.8a1.6 1.6 0 0 1 0 2.3Z"/><path d="M7.5 7.5h.01"/>',
        'chart-bar' => '<path d="M3 21h18"/><rect x="5" y="11" width="4" height="7" rx="1"/><rect x="10.5" y="6" width="4" height="12" rx="1"/><rect x="16" y="14" width="4" height="4" rx="1"/>',
        'chart-pie' => '<path d="M21 12A9 9 0 1 1 12 3v9z"/><path d="M15.5 3.8A9 9 0 0 1 20.2 8.5L13 11.5z"/>',
        'users' => '<path d="M16 20v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V20"/><circle cx="9" cy="7.5" r="3.5"/><path d="M22 20v-1.5a4 4 0 0 0-3-3.87"/><path d="M16.5 4.13a4 4 0 0 1 0 7.75"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0"/>',
        'user-minus' => '<circle cx="10" cy="8" r="4"/><path d="M2.5 20.5a7.5 7.5 0 0 1 15 0"/><path d="M17 11h5"/>',
        'bell' => '<path d="M18 8.5a6 6 0 0 0-12 0c0 6-2.5 7.5-2.5 7.5h17S18 14.5 18 8.5"/><path d="M13.7 20a2 2 0 0 1-3.4 0"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .33 1.76l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.6 1.6 0 0 0-1.76-.33 1.6 1.6 0 0 0-1 1.47V21a2 2 0 1 1-4 0v-.11a1.6 1.6 0 0 0-1.05-1.46 1.6 1.6 0 0 0-1.76.32l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.6 1.6 0 0 0 .33-1.76 1.6 1.6 0 0 0-1.47-1H3a2 2 0 1 1 0-4h.11a1.6 1.6 0 0 0 1.46-1.05 1.6 1.6 0 0 0-.32-1.76l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.6 1.6 0 0 0 1.76.33H9a1.6 1.6 0 0 0 1-1.47V3a2 2 0 1 1 4 0v.11a1.6 1.6 0 0 0 1 1.47 1.6 1.6 0 0 0 1.76-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.6 1.6 0 0 0-.33 1.76V9a1.6 1.6 0 0 0 1.47 1H21a2 2 0 1 1 0 4h-.11a1.6 1.6 0 0 0-1.47 1Z"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',

        // Actions
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'filter' => '<path d="M3.5 5h17l-6.5 8v6l-4 2v-8z"/>',
        'pencil' => '<path d="M4 20h4L19.5 8.5a2.12 2.12 0 0 0-3-3L5 17z"/><path d="m14.5 6.5 3 3"/>',
        'trash' => '<path d="M4 7h16"/><path d="M9.5 7V5.5a1.5 1.5 0 0 1 1.5-1.5h2a1.5 1.5 0 0 1 1.5 1.5V7"/><path d="M6.5 7v12.5A1.5 1.5 0 0 0 8 21h8a1.5 1.5 0 0 0 1.5-1.5V7"/><path d="M10.5 11v6M13.5 11v6"/>',
        'download' => '<path d="M12 3v12"/><path d="m7.5 11 4.5 4 4.5-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
        'refresh' => '<path d="M20.5 12a8.5 8.5 0 1 1-2.5-6"/><path d="M20.5 4v5h-5"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 2.64-6.36L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
        'expand' => '<path d="M9 3H5a2 2 0 0 0-2 2v4"/><path d="M15 3h4a2 2 0 0 1 2 2v4"/><path d="M9 21H5a2 2 0 0 1-2-2v-4"/><path d="M15 21h4a2 2 0 0 0 2-2v-4"/>',
        'compress' => '<path d="M8 3v3a2 2 0 0 1-2 2H3"/><path d="M16 3v3a2 2 0 0 0 2 2h3"/><path d="M8 21v-3a2 2 0 0 0-2-2H3"/><path d="M16 21v-3a2 2 0 0 1 2-2h3"/>',
        'external-link' => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5"/>',
        'mic' => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0"/><path d="M12 18v3"/><path d="M9 21h6"/>',
        'more-vertical' => '<circle cx="12" cy="5" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="12" cy="19" r="1.3"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M4 4l16 16"/><path d="M10.7 6.2A8.6 8.6 0 0 1 12 6c6 0 9.5 6 9.5 6a15 15 0 0 1-3 3.6"/><path d="M6.6 7.7A15 15 0 0 0 2.5 12S6 18 12 18a8.8 8.8 0 0 0 3.4-.66"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',

        // Arrows & chevrons
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-up' => '<path d="m6 15 6-6 6 6"/>',
        'chevron-left' => '<path d="m15 6-6 6 6 6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'arrow-up' => '<path d="M12 19V5"/><path d="m5.5 11.5 6.5-6.5 6.5 6.5"/>',
        'arrow-down' => '<path d="M12 5v14"/><path d="m5.5 12.5 6.5 6.5 6.5-6.5"/>',
        'arrow-right' => '<path d="M4 12h16"/><path d="m13.5 5.5 6.5 6.5-6.5 6.5"/>',
        'trending-up' => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'trending-down' => '<path d="m3 7 6 6 4-4 8 8"/><path d="M15 17h6v-6"/>',
        'arrow-up-right' => '<path d="M7 17 17 7"/><path d="M8 7h9v9"/>',
        'arrows-up-down' => '<path d="M7 4v16"/><path d="m3.5 7.5 3.5-3.5 3.5 3.5"/><path d="M17 20V4"/><path d="m13.5 16.5 3.5 3.5 3.5-3.5"/>',

        // Status
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.5 2.5L16 9.5"/>',
        'x-circle' => '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
        'exclamation-triangle' => '<path d="M10.3 4.2 2.6 17.5A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z"/><path d="M12 9.5v4"/><path d="M12 17h.01"/>',
        'info-circle' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
        'shield' => '<path d="M12 3 4.5 6v6c0 4.4 3 8.2 7.5 9.4 4.5-1.2 7.5-5 7.5-9.4V6z"/><path d="m9.5 12 1.8 1.8 3.4-3.6"/>',
        'lock' => '<rect x="4.5" y="10" width="15" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 8-8"/><path d="m17.5 5.5 2 2"/><path d="m14.5 8.5 2 2"/>',
        'sparkles' => '<path d="m12 3 1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9z"/><path d="M18.5 16.5 19.5 19l2.5 1-2.5 1-1 2.5-1-2.5-2.5-1 2.5-1z"/>',
        'bot' => '<rect x="4" y="8" width="16" height="12" rx="3"/><path d="M12 8V5"/><circle cx="12" cy="5" r="1"/><circle cx="9" cy="14" r="1"/><circle cx="15" cy="14" r="1"/><path d="M9 17.5h6"/>',

        // Install
        'phone-add' => '<rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M11 18.5h2"/><path d="M12 7v6M9 10h6"/>',
        'share-ios' => '<path d="M12 3.5v11"/><path d="m8.5 7 3.5-3.5L15.5 7"/><path d="M8 10.5H6.5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-6.5a2 2 0 0 0-2-2H16"/>',
        'square-plus' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4"/><path d="M12 8.5v7M8.5 12h7"/>',

        // Preferences
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/>',
        'moon' => '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'palette' => '<path d="M12 3a9 9 0 0 0 0 18 2 2 0 0 0 1.6-3.2 2 2 0 0 1 1.6-3.2H18a3 3 0 0 0 3-3A9 9 0 0 0 12 3Z"/><circle cx="7.5" cy="12" r="1.2"/><circle cx="9.5" cy="7.5" r="1.2"/><circle cx="14.5" cy="7.5" r="1.2"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3.5 9h17M3.5 15h17"/><path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18Z"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 10h17"/><path d="M8 3v4M16 3v4"/>',
        'credit-card' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/><path d="M6.5 15h3"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3.5 12.5 8.5 4.7 8.5-4.7"/><path d="m3.5 16.8 8.5 4.7 8.5-4.7"/>',

        // Categories
        'banknote' => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
        'gift' => '<rect x="3.5" y="8" width="17" height="4" rx="1"/><path d="M5 12v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8"/><path d="M12 8v13"/><path d="M12 8S10.5 4 8.5 4a2 2 0 0 0 0 4Zm0 0s1.5-4 3.5-4a2 2 0 0 1 0 4Z"/>',
        'briefcase' => '<rect x="2.5" y="7" width="19" height="13" rx="2"/><path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7"/><path d="M2.5 12.5h19"/>',
        'utensils' => '<path d="M6 3v8a2 2 0 0 0 4 0V3"/><path d="M8 11v10"/><path d="M17.5 3c-1.4 1-2.5 2.8-2.5 5s1 3.5 2.5 3.5V21"/>',
        'car' => '<path d="M5 17h14"/><path d="M6.5 17v2H4v-2"/><path d="M20 19h-2.5v-2"/><path d="M3.5 17v-4l2-5h13l2 5v4z"/><circle cx="7.5" cy="13.5" r="1"/><circle cx="16.5" cy="13.5" r="1"/>',
        'shopping-bag' => '<path d="M5 8h14l1 12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/><path d="M9 11V7a3 3 0 0 1 6 0v4"/>',
        'heart' => '<path d="M12 20s-7.5-4.4-7.5-9.5A4.5 4.5 0 0 1 12 7.6a4.5 4.5 0 0 1 7.5 2.9C19.5 15.6 12 20 12 20Z"/>',
        'heart-pulse' => '<path d="M12 20s-7.5-4.4-7.5-9.5A4.5 4.5 0 0 1 12 7.6a4.5 4.5 0 0 1 7.5 2.9C19.5 15.6 12 20 12 20Z"/><path d="M3.5 13h4l1.5-2.5 2 5 1.5-3h4"/>',
        'graduation-cap' => '<path d="m12 4 9.5 4.5L12 13 2.5 8.5z"/><path d="M6 10.5V16c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5v-5.5"/>',
        'film' => '<rect x="3" y="4.5" width="18" height="15" rx="2"/><path d="M7.5 4.5v15M16.5 4.5v15"/><path d="M3 12h18M3 8.2h4.5M3 15.8h4.5M16.5 8.2H21M16.5 15.8H21"/>',
        'hand-coins' => '<circle cx="16.5" cy="6.5" r="3"/><path d="M3 14.5 6 12h5l2 1.5a1.4 1.4 0 0 1-1 2.5h-3"/><path d="M13 15.5h3l4-2.5a1.5 1.5 0 0 1 1.6 2.5L15 21H8l-5-3.5"/>',
        'plane' => '<path d="M21 15.5 3 10V7l3 .8 1.7 2.3 5-1.4L9.5 4l3-.8 5 5.3 3.5-1a1.6 1.6 0 0 1 1 3l-2 .8v3.5l-2 .7z"/>',
        'phone' => '<rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
        'zap' => '<path d="M13.5 2.5 4 14h7l-.5 7.5L20 10h-7z"/>',
        'droplet' => '<path d="M12 3s6 6.2 6 10a6 6 0 0 1-12 0c0-3.8 6-10 6-10Z"/>',
        'wifi' => '<path d="M2.5 9a15 15 0 0 1 19 0"/><path d="M6 12.6a10 10 0 0 1 12 0"/><path d="M9.5 16.2a5 5 0 0 1 5 0"/><path d="M12 19.8h.01"/>',
        'shirt' => '<path d="M8 3 4 5.5l1.5 4L8 8.5V21h8V8.5l2.5 1 1.5-4L16 3l-2 2h-4z"/>',
        'baby' => '<circle cx="12" cy="8" r="4.5"/><path d="M10 7.5h.01M14 7.5h.01"/><path d="M10.5 10.5a2.2 2.2 0 0 0 3 0"/><path d="M6.5 20.5c1.5-3 3.5-4.5 5.5-4.5s4 1.5 5.5 4.5"/>',
        'paw' => '<circle cx="8" cy="7" r="2"/><circle cx="16" cy="7" r="2"/><circle cx="5" cy="12.5" r="2"/><circle cx="19" cy="12.5" r="2"/><path d="M12 11c2.5 0 4.5 2.4 4.5 5A3 3 0 0 1 13.5 19h-3a3 3 0 0 1-3-3c0-2.6 2-5 4.5-5Z"/>',
        'dumbbell' => '<path d="M3 12h18"/><rect x="4" y="8.5" width="3" height="7" rx="1"/><rect x="17" y="8.5" width="3" height="7" rx="1"/><path d="M2 10.5v3M22 10.5v3"/>',
        'coffee' => '<path d="M4 8h13v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5z"/><path d="M17 10h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M7 3.5v2M11 3.5v2"/>',
        'fuel' => '<path d="M4 21V5a2 2 0 0 1 2-2h5a2 2 0 0 1 2 2v16"/><path d="M3 21h11"/><path d="M6 9h5"/><path d="M13 8.5h3.5a1.5 1.5 0 0 1 1.5 1.5v6.5a1.5 1.5 0 0 0 3 0V11l-2.5-3"/>',
        'piggy-bank' => '<path d="M18.5 10.5a6 6 0 0 0-5-3h-3a6 6 0 0 0-6 6c0 1.9 1 3.6 2.5 4.6V21h3v-1.5h4V21h3v-3.2c1-.7 1.7-1.7 2-2.8H21v-3.5z"/><path d="M13.5 7.5 12 4l4 1.5z"/><path d="M15 12.5h.01"/>',
    ];

    $svg = $icons[$name] ?? $icons['tag'];
@endphp

<svg
    {{ $attributes->merge(['class' => $class]) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>{!! $svg !!}</svg>
