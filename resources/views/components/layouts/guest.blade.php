<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ isset($title) ? $title.' · '.__('common.app_name') : __('common.app_name') }}</title>

    <x-pwa-meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-theme-style />

    {{--
    | These pages render no Livewire component, so Livewire would not inject
    | its script on its own, and without it there is no Alpine to run the
    | password field's show/hide toggle.
    --}}
    @livewireStyles
</head>
<body class="relative min-h-dvh bg-background text-foreground antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <span
            class="absolute -top-40 -left-32 size-[32rem] rounded-full opacity-25 blur-3xl"
            style="background: radial-gradient(circle, var(--color-primary) 0%, transparent 68%)"
        ></span>
        <span
            class="absolute -right-32 -bottom-40 size-[32rem] rounded-full opacity-20 blur-3xl"
            style="background: radial-gradient(circle, var(--color-accent) 0%, transparent 68%)"
        ></span>
    </div>

    <div class="flex min-h-dvh flex-col">
        <header class="mx-auto flex w-full max-w-md items-center justify-between gap-3 px-5 py-5 sm:max-w-lg">
            <x-logo />
        </header>

        <main class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-5 pb-10 sm:max-w-lg">
            <div class="panel p-6 sm:p-8">
                @isset($heading)
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold tracking-tight text-balance text-foreground">{{ $heading }}</h1>
                        @isset($subheading)
                            <p class="mt-1.5 text-sm text-pretty text-muted-foreground">{{ $subheading }}</p>
                        @endisset
                    </div>
                @endisset

                {{ $slot }}
            </div>

            <x-install-button class="mt-6" />
        </main>
    </div>

    <x-install-guide />

    @livewireScripts
</body>
</html>
