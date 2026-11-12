@props(['title', 'subtitle' => null])

@php
    use App\Support\NavigationMenu;

    $sections = NavigationMenu::settings();
@endphp

<div class="space-y-4">
    <x-page-header :title="__('settings.title')" />

    <div class="grid gap-4 lg:grid-cols-4">
        {{-- On a phone this becomes a horizontally scrolling strip of tabs. --}}
        <nav
            class="scrollbar-none -mx-4 flex gap-1.5 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:px-0"
            aria-label="{{ __('navigation.settings_sections') }}"
        >
            @foreach ($sections as $section)
                <a
                    href="{{ $section->url }}"
                    wire:navigate
                    class="nav-link shrink-0 {{ $section->active ? 'nav-link-active' : '' }}"
                    @if ($section->active) aria-current="page" @endif
                >
                    <x-icon :name="$section->icon" class="size-4 shrink-0" />
                    <span class="whitespace-nowrap">{{ $section->label }}</span>
                </a>
            @endforeach
        </nav>

        <div class="space-y-4 lg:col-span-3">
            <div>
                <h2 class="text-lg font-semibold text-foreground">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-1 text-sm text-pretty text-muted-foreground">{{ $subtitle }}</p>
                @endif
            </div>

            {{ $slot }}
        </div>
    </div>
</div>
