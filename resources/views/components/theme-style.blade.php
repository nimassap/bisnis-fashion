@php
    use App\Support\ThemePalette;
@endphp

{{--
| The user's palette is written straight into the page.
|
| Rendering it server-side means the right colours are there on the very first
| paint — no flash of a light theme before a dark one loads, and no JavaScript
| needed to apply a preference.
--}}
<style>{!! ThemePalette::styleSheet(auth()->user()) !!}</style>
