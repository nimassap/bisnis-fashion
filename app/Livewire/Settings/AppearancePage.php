<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\ThemeMode;
use App\Livewire\Concerns\SendsToasts;
use App\Support\Color;
use App\Support\ThemePalette;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The theme picker, including a full custom palette.
 *
 * Because every component reads its colours from CSS custom properties, the
 * seven values edited here restyle the entire interface — buttons, cards,
 * navigation, forms, tables, charts, badges and notifications alike.
 */
#[Layout('components.layouts.app')]
final class AppearancePage extends Component
{
    use SendsToasts;

    public string $themeMode = 'system';

    public string $themePreset = 'blue';

    /** @var array<string, string> */
    public array $colors = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->themeMode = $user->theme_mode->value;
        $this->themePreset = $user->theme_preset;
        $this->colors = ThemePalette::baseColors($user, 'light');
    }

    /**
     * Switching to a custom theme starts from whatever is on screen now, so
     * nobody is dropped onto a blank palette.
     */
    public function updatedThemeMode(string $value): void
    {
        if ($value === ThemeMode::Custom->value && $this->colors === []) {
            $this->loadPreset($this->themePreset, 'light');
        }
    }

    public function loadPreset(string $preset, string $scheme = 'light'): void
    {
        $this->themePreset = array_key_exists($preset, ThemePalette::presets())
            ? $preset
            : config('theme.default_preset');

        $this->colors = ThemePalette::preset($this->themePreset, $scheme === 'dark' ? 'dark' : 'light');
    }

    public function selectPreset(string $preset): void
    {
        $this->themePreset = array_key_exists($preset, ThemePalette::presets())
            ? $preset
            : config('theme.default_preset');

        if ($this->themeMode === ThemeMode::Custom->value) {
            $this->colors = ThemePalette::preset($this->themePreset, 'light');
        }
    }

    public function resetTheme(): void
    {
        auth()->user()->forceFill([
            'theme_mode' => config('theme.default_mode'),
            'theme_preset' => config('theme.default_preset'),
            'theme_colors' => null,
        ])->save();

        session()->flash('status', __('settings.theme_reset'));

        // A full page load is what swaps the palette in <head>.
        $this->redirectRoute('settings.appearance');
    }

    public function save(): void
    {
        $validated = $this->validate();

        auth()->user()->forceFill([
            'theme_mode' => $validated['themeMode'],
            'theme_preset' => $validated['themePreset'],
            'theme_colors' => $validated['themeMode'] === ThemeMode::Custom->value
                ? array_map(Color::normalizeHex(...), $validated['colors'])
                : null,
        ])->save();

        $this->flashSuccess(__('settings.appearance_updated'));

        /*
        | A genuine reload, not `refreshShell()`'s wire:navigate one — this is
        | the one settings page where that distinction matters.
        |
        | Every other page `refreshShell()` sends through redraws something in
        | <body>, which is exactly what Livewire's client-side navigation is
        | for. The palette is a <style> block in <head> (see
        | x-theme-style), and Livewire's head-merging only replaces a <link>
        | or <script> it recognises as already there; a plain <style> tag
        | whose content has changed is simply appended alongside the old one
        | rather than swapping it out, because nothing here ever removes it.
        | Saved a new theme and navigated back to this same page, both the old
        | and the new palette were sitting in <head> at once from then on.
        |
        | `resetTheme()` beside this has always known that and reloaded
        | properly; this forgot it the day it was folded into the same
        | wire:navigate helper as everything else.
        */
        $this->redirectRoute('settings.appearance');
    }

    /**
     * The palette as it would look if saved, used to drive the live preview.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function previewTokens(): array
    {
        if ($this->themeMode === ThemeMode::Custom->value) {
            return ThemePalette::tokens($this->normalisedColors());
        }

        $scheme = $this->themeMode === ThemeMode::Dark->value ? 'dark' : 'light';

        return ThemePalette::tokens(ThemePalette::preset($this->themePreset, $scheme));
    }

    /**
     * Ready-to-inline CSS custom properties for the preview panel.
     */
    #[Computed]
    public function previewStyle(): string
    {
        $declarations = '';

        foreach ($this->previewTokens() as $name => $value) {
            $declarations .= "--color-{$name}:{$value};";
        }

        return $declarations;
    }

    /**
     * Whether the chosen text colour is readable on the chosen surfaces.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function contrast(): array
    {
        $tokens = $this->previewTokens();

        $ratio = min(
            Color::contrastRatio($tokens['foreground'], $tokens['background']),
            Color::contrastRatio($tokens['card-foreground'], $tokens['card']),
        );

        $minimum = (float) config('theme.minimum_contrast_ratio');

        return [
            'ratio' => round($ratio, 2),
            'minimum' => $minimum,
            'passes' => $ratio >= $minimum,
        ];
    }

    public function isCustom(): bool
    {
        return $this->themeMode === ThemeMode::Custom->value;
    }

    /**
     * @return array<string, string>
     */
    private function normalisedColors(): array
    {
        $preset = ThemePalette::preset($this->themePreset, 'light');

        $valid = array_filter(
            $this->colors,
            fn ($value, $token) => is_string($value)
                && Color::isValidHex($value)
                && in_array($token, ThemePalette::editableTokens(), true),
            ARRAY_FILTER_USE_BOTH,
        );

        return array_map(Color::normalizeHex(...), $valid) + $preset;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'themeMode' => ['required', Rule::in(config('theme.modes'))],
            'themePreset' => ['required', Rule::in(array_keys(ThemePalette::presets()))],
            'colors' => ['array'],
            'colors.*' => ['required', 'string', 'regex:/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        $attributes = [
            'themeMode' => __('settings.theme_mode'),
            'themePreset' => __('settings.theme_preset'),
        ];

        foreach (ThemePalette::editableTokens() as $token) {
            $attributes["colors.{$token}"] = __("settings.color_{$token}");
        }

        return $attributes;
    }

    public function render(): View
    {
        return view('livewire.settings.appearance-page')
            ->title(__('settings.appearance_title'));
    }
}
