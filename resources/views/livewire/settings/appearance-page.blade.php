@php
    use App\Enums\ThemeMode;
    use App\Support\ThemePalette;
    use App\Support\Money;

    $presets = ThemePalette::presets();
    $tokens = ThemePalette::editableTokens();
    $contrast = $this->contrast;

    $modeIcons = [
        'light' => 'sun',
        'dark' => 'moon',
        'system' => 'monitor',
        'custom' => 'palette',
    ];
@endphp

<div>
    <x-settings-shell :title="__('settings.appearance_title')" :subtitle="__('settings.appearance_subtitle')">
        <form wire:submit="save" class="space-y-4">
            {{-- Mode --}}
            <fieldset class="panel-padded">
                <legend class="card-title">{{ __('settings.theme_mode') }}</legend>

                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach (ThemeMode::cases() as $mode)
                        <label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border px-3 py-3.5 text-center transition {{ $themeMode === $mode->value ? 'border-primary bg-primary-soft text-primary' : 'border-hairline text-muted-foreground hover:bg-secondary' }}">
                            <input type="radio" class="sr-only" wire:model.live="themeMode" value="{{ $mode->value }}" />
                            <x-icon :name="$modeIcons[$mode->value]" class="size-5" />
                            <span class="text-xs leading-tight font-medium">{{ $mode->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            {{-- Presets --}}
            <fieldset class="panel-padded">
                <legend class="card-title">
                    {{ $this->isCustom() ? __('settings.load_preset') : __('settings.theme_preset') }}
                </legend>

                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($presets as $name => $preset)
                        <button
                            type="button"
                            wire:click="selectPreset('{{ $name }}')"
                            class="flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left transition {{ $themePreset === $name ? 'border-primary bg-primary-soft' : 'border-hairline hover:bg-secondary' }}"
                            @if ($themePreset === $name) aria-current="true" @endif
                        >
                            <span class="flex shrink-0 gap-0.5" aria-hidden="true">
                                <span class="size-4 rounded-full" style="background-color: {{ $preset['light']['primary'] }}"></span>
                                <span class="-ml-2 size-4 rounded-full" style="background-color: {{ $preset['light']['accent'] }}"></span>
                            </span>

                            <span class="min-w-0 flex-1 truncate text-xs font-medium text-foreground">{{ $preset['label'] }}</span>

                            @if ($themePreset === $name)
                                <x-icon name="check" class="size-3.5 shrink-0 text-primary" />
                            @endif
                        </button>
                    @endforeach
                </div>

                @if ($this->isCustom())
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-hairline pt-3">
                        <button type="button" class="btn-secondary btn-sm" wire:click="loadPreset('{{ $themePreset }}', 'light')">
                            <x-icon name="sun" class="size-3.5" />
                            <span>{{ __('enums.theme_mode.light') }}</span>
                        </button>

                        <button type="button" class="btn-secondary btn-sm" wire:click="loadPreset('{{ $themePreset }}', 'dark')">
                            <x-icon name="moon" class="size-3.5" />
                            <span>{{ __('enums.theme_mode.dark') }}</span>
                        </button>
                    </div>
                @endif
            </fieldset>

            {{-- Custom colours --}}
            @if ($this->isCustom())
                <fieldset class="panel-padded">
                    <legend class="card-title">{{ __('settings.custom_theme') }}</legend>
                    <p class="card-hint mt-1">{{ __('settings.custom_theme_hint') }}</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($tokens as $token)
                            <x-field
                                :label="__('settings.color_'.$token)"
                                :for="'color-'.$token"
                                :error="$errors->first('colors.'.$token)"
                                required
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        type="color"
                                        id="color-{{ $token }}"
                                        wire:model.live="colors.{{ $token }}"
                                        class="size-11 shrink-0 cursor-pointer rounded-xl border border-input bg-card p-1"
                                        aria-label="{{ __('settings.color_'.$token) }}"
                                    />

                                    <input
                                        type="text"
                                        wire:model.live.debounce.500ms="colors.{{ $token }}"
                                        class="input numeric font-mono uppercase @error('colors.'.$token) input-invalid @enderror"
                                        maxlength="7"
                                        spellcheck="false"
                                        aria-label="{{ __('settings.color_'.$token) }} — {{ __('settings.hex_code') }}"
                                    />
                                </div>
                            </x-field>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            {{-- Live preview --}}
            <section class="panel-padded">
                <h3 class="card-title">{{ __('settings.preview') }}</h3>
                <p class="card-hint mt-0.5">{{ __('settings.preview_hint') }}</p>

                {{-- Scoped custom properties: the preview restyles itself without touching the page. --}}
                <div
                    class="mt-4 rounded-2xl border p-4"
                    style="{{ $this->previewStyle }} background-color: var(--color-background); border-color: var(--color-border); color: var(--color-foreground);"
                >
                    <div class="rounded-xl border p-4" style="background-color: var(--color-card); border-color: var(--color-border);">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs" style="color: var(--color-muted-foreground);">{{ __('settings.preview_balance') }}</p>
                                <p class="numeric text-lg font-semibold">{{ Money::format('12500000') }}</p>
                            </div>

                            <span class="flex size-9 items-center justify-center rounded-lg" style="background-color: var(--color-primary-soft); color: var(--color-primary);">
                                <x-icon name="wallet" class="size-4" />
                            </span>
                        </div>

                        <div class="mt-3.5 h-2.5 w-full overflow-hidden rounded-full" style="background-color: var(--color-secondary);">
                            <div class="h-full w-2/3 rounded-full" style="background-color: var(--color-primary);"></div>
                        </div>

                        <div class="mt-3.5 flex flex-wrap gap-2">
                            <span class="badge" style="background-color: var(--color-success-soft); color: var(--color-success);">
                                <x-icon name="check-circle" class="size-3.5" />{{ __('enums.budget_status.safe') }}
                            </span>
                            <span class="badge" style="background-color: var(--color-warning-soft); color: var(--color-warning);">
                                <x-icon name="exclamation-triangle" class="size-3.5" />{{ __('enums.budget_status.warning') }}
                            </span>
                            <span class="badge" style="background-color: var(--color-danger-soft); color: var(--color-danger);">
                                <x-icon name="x-circle" class="size-3.5" />{{ __('enums.budget_status.exceeded') }}
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="btn btn-sm" style="background-color: var(--color-primary); color: var(--color-primary-foreground);">
                                {{ __('common.save') }}
                            </span>
                            <span class="btn btn-sm border" style="background-color: var(--color-card); border-color: var(--color-border); color: var(--color-foreground);">
                                {{ __('common.cancel') }}
                            </span>
                            <span class="btn btn-sm" style="background-color: var(--color-accent); color: var(--color-accent-foreground);">
                                {{ __('common.details') }}
                            </span>
                        </div>

                        <input
                            type="text"
                            class="mt-3.5 w-full rounded-xl border px-3.5 py-2.5 text-sm"
                            style="background-color: var(--color-card); border-color: var(--color-input); color: var(--color-foreground);"
                            placeholder="{{ __('settings.preview_placeholder') }}"
                            readonly
                            tabindex="-1"
                            aria-hidden="true"
                        />
                    </div>
                </div>

                {{-- Readability check --}}
                <div class="mt-3.5 flex items-start gap-2 rounded-xl border p-3 text-xs {{ $contrast['passes'] ? 'border-success/40 bg-success-soft text-success' : 'border-warning/40 bg-warning-soft text-warning' }}">
                    <x-icon :name="$contrast['passes'] ? 'check-circle' : 'exclamation-triangle'" class="mt-0.5 size-4 shrink-0" />
                    <p class="text-pretty">
                        {{ $contrast['passes']
                            ? __('settings.contrast_ok', ['ratio' => $contrast['ratio']])
                            : __('settings.contrast_warning', ['ratio' => $contrast['ratio'], 'minimum' => $contrast['minimum']]) }}
                    </p>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                <button type="button" class="btn-ghost" wire:click="resetTheme">
                    <x-icon name="refresh" class="size-4" />
                    <span>{{ __('settings.reset_theme') }}</span>
                </button>

                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </form>
    </x-settings-shell>
</div>
