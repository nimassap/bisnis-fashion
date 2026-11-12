@php
    use App\Support\ThemePalette;

    $themeColors = ThemePalette::browserThemeColors(auth()->user());
@endphp

{{--
| Everything a phone needs to treat this as an installable application.
|
| With these in place the site can be added to a home screen: it opens from its
| own icon, fills the screen without browser chrome, and shows a splash in the
| app's colours instead of a blank white flash.
--}}
<link rel="manifest" href="{{ route('manifest') }}">

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
<link rel="icon" href="{{ asset('icons/icon-192.png') }}" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

{{--
| The status bar tint follows the signed-in user's own theme, which the
| manifest cannot: it is read once at install time and never again. Somebody
| following their system gets both colours behind a media query.
--}}
@if (isset($themeColors['fixed']))
    <meta name="theme-color" content="{{ $themeColors['fixed'] }}">
@else
    <meta name="theme-color" content="{{ $themeColors['light'] }}" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="{{ $themeColors['dark'] }}" media="(prefers-color-scheme: dark)">
@endif

{{-- iOS reads its own tags rather than the manifest. --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ __('common.app_short_name') }}">
<meta name="application-name" content="{{ __('common.app_short_name') }}">
<meta name="mobile-web-app-capable" content="yes">

{{--
| The install prompt, caught before the browser hides it away.
|
| Chrome and Edge fire `beforeinstallprompt` once the site qualifies and then
| tuck the offer into an "Open in app" chip in the address bar, which is easy
| to miss and impossible to point somebody at. Holding on to the event lets the
| app ask in its own words, from a button in the page — see the `install` store
| in resources/js/app.js.
|
| This has to be listened for in the head: the event fires early, and a module
| script would still be waiting on the parser when it does.
--}}
<script>
    // Livewire re-runs head scripts it has not seen before. The guard keeps a
    // page-to-page navigation from stacking up a second set of listeners.
    if (! window.installPrompt) {
        window.installPrompt = { event: null }

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault()
            window.installPrompt.event = event
            window.dispatchEvent(new CustomEvent('install-available'))
        })

        window.addEventListener('appinstalled', () => {
            window.installPrompt.event = null
        })
    }
</script>
