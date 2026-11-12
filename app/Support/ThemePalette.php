<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ThemeMode;
use App\Models\User;

/**
 * Turns a user's theme preference into the CSS custom properties every
 * component reads.
 *
 * Components only ever reference these variables, which is what allows the
 * whole interface — buttons, cards, navigation, charts, badges — to restyle
 * itself from seven user-chosen colours without touching a single component.
 */
final class ThemePalette
{
    /**
     * Seed colours a user may override in Custom Theme mode.
     *
     * @return array<int, string>
     */
    public static function editableTokens(): array
    {
        return config('theme.tokens');
    }

    /**
     * @return array<string, array{label: string, light: array<string, string>, dark: array<string, string>}>
     */
    public static function presets(): array
    {
        return config('theme.presets');
    }

    /**
     * @return array<string, string>
     */
    public static function preset(string $name, string $scheme): array
    {
        $presets = self::presets();
        $preset = $presets[$name] ?? $presets[config('theme.default_preset')];

        return $preset[$scheme];
    }

    /**
     * The seven base colours a user is currently themed with.
     *
     * @return array<string, string>
     */
    public static function baseColors(?User $user, string $scheme): array
    {
        $mode = $user?->theme_mode ?? ThemeMode::from(config('theme.default_mode'));
        $preset = self::preset($user?->theme_preset ?? config('theme.default_preset'), $scheme);

        if ($mode !== ThemeMode::Custom) {
            return $preset;
        }

        // A custom palette falls back to its preset for anything not overridden,
        // so a half-finished palette still renders a usable interface.
        $custom = array_filter(
            $user?->theme_colors ?? [],
            fn ($value, $token) => is_string($value)
                && Color::isValidHex($value)
                && in_array($token, self::editableTokens(), true),
            ARRAY_FILTER_USE_BOTH,
        );

        return array_map(Color::normalizeHex(...), $custom) + $preset;
    }

    /**
     * Expand seven base colours into the full set of design tokens.
     *
     * @param  array<string, string>  $base
     * @return array<string, string>
     */
    public static function tokens(array $base): array
    {
        $card = $base['card'];
        $text = $base['text'];
        $background = $base['background'];
        $primary = $base['primary'];
        $accent = $base['accent'];
        $surfaceIsDark = Color::isDark($card);

        $spotlight = $surfaceIsDark
            ? Color::mix($background, '#000000', 0.45)
            : Color::mix($text, '#000000', 0.12);
        $spotlightText = Color::readableForeground($spotlight);

        return [
            'background' => $background,

            'foreground' => $text,
            'card' => $card,
            'card-foreground' => $text,
            'sidebar' => $base['sidebar'],
            'sidebar-foreground' => Color::isDark($base['sidebar'])
                ? Color::mix($text, '#ffffff', $surfaceIsDark ? 0.0 : 0.9)
                : $text,
            'border' => $base['border'],
            'input' => $base['border'],
            'primary' => $primary,
            'primary-foreground' => Color::readableForeground($primary),
            'primary-soft' => Color::mix($card, $primary, $surfaceIsDark ? 0.22 : 0.12),
            'primary-hover' => Color::mix($primary, $surfaceIsDark ? '#ffffff' : '#000000', 0.12),
            'accent' => $accent,
            'accent-foreground' => Color::readableForeground($accent),
            'accent-soft' => Color::mix($card, $accent, $surfaceIsDark ? 0.22 : 0.12),
            'secondary' => Color::mix($card, $text, $surfaceIsDark ? 0.12 : 0.06),
            'secondary-foreground' => $text,
            'muted' => Color::mix($card, $text, $surfaceIsDark ? 0.08 : 0.04),
            // Secondary text is a 42% blend towards the card, held to 3:1 in
            // case that blend lands too close to the card it sits on.
            'muted-foreground' => Color::ensureContrast(Color::mix($text, $card, 0.42), $card, 3.0),
            'ring' => $primary,
            'overlay' => $surfaceIsDark ? '#000000' : '#0f172a',

            // Raised tiles that sit *inside* a card — the mini panels a metric
            // card is built from. They step away from the card rather than
            // towards the page, so nesting stays legible on either scheme.
            'surface' => Color::mix($card, $text, $surfaceIsDark ? 0.07 : 0.04),
            'surface-strong' => Color::mix($card, $text, $surfaceIsDark ? 0.13 : 0.07),

            // The one deliberately high-contrast panel on the page. On a light
            // palette it is near-black; on a dark one it is the deepest surface
            // available, so it reads as a feature either way.
            //
            // Its text is derived from the panel rather than from the theme, so
            // a custom palette cannot land light type on a light panel however
            // unusual the seven colours it was built from.
            'spotlight' => $spotlight,
            'spotlight-foreground' => $spotlightText,
            'spotlight-muted' => Color::mix($spotlightText, $spotlight, 0.42),

            // A border that whispers. Used where a full border would chop the
            // page into boxes but some separation is still needed.
            'hairline' => Color::mix($base['border'], $card, 0.5),

            /*
            | Dora's own two-stop gradient, the ring her launcher wears, and
            | the chip every badge sits her mark on.
            |
            | The one part of the palette deliberately not derived from the
            | seven colours a user picked. She is a mark, not a component:
            | recognisably the same indigo-into-blue wherever she turns up and
            | whichever preset the rest of the app is wearing, the way a logo
            | does not repaint itself to match the page it sits on. That has
            | to include what she sits on — a fixed star inside a badge that
            | still followed the preset was a green circle in Emerald and a
            | red one in Rose, around a star that never changed at all.
            |
            | What she does follow is the surface. These three values are
            | chosen by hand rather than mixed, and the mark's own pair are
            | the same hue family two steps apart on the same scale the
            | presets themselves step along for dark mode: a 600-weight pair
            | reads on white and goes muddy on near-black, a 400-weight pair
            | reads on near-black and washes out on white. The badge follows
            | the same logic at far lower saturation, since it is a
            | background rather than the thing being looked at.
            */
            'assistant-mark' => $surfaceIsDark ? '#818cf8' : '#4f46e5',
            'assistant-mark-end' => $surfaceIsDark ? '#60a5fa' : '#2563eb',
            'assistant-badge' => $surfaceIsDark ? '#312e81' : '#eef2ff',
        ] + self::statusTokens($card, $surfaceIsDark);
    }

    /**
     * Depth: the shadows, glows and veils that make a card read as a physical
     * object rather than a rectangle.
     *
     * Light and dark need genuinely different physics — a light theme gets a
     * cast shadow, a dark one gets a lit top edge, because a drop shadow is
     * invisible against near-black. Keeping both here means no component has
     * to know which scheme it is being painted in.
     *
     * @param  array<string, string>  $tokens
     * @return array<string, string>
     */
    public static function effects(array $tokens): array
    {
        $isDark = Color::isDark($tokens['card']);
        $shade = Color::toRgbChannels($isDark ? '#000000' : '#1e293b');
        $primary = Color::toRgbChannels($tokens['primary']);

        return $isDark ? [
            'shadow-card' => "inset 0 1px 0 0 rgb(255 255 255 / 0.06), 0 1px 2px 0 rgb({$shade} / 0.6), 0 16px 40px -24px rgb({$shade} / 0.9)",
            'shadow-lift' => "inset 0 1px 0 0 rgb(255 255 255 / 0.08), 0 24px 60px -28px rgb({$shade} / 1)",
            'shadow-glow' => "0 0 0 1px rgb({$primary} / 0.35), 0 18px 45px -20px rgb({$primary} / 0.55)",
            'veil' => 'linear-gradient(160deg, rgb(255 255 255 / 0.055) 0%, rgb(255 255 255 / 0) 46%)',
            'veil-strong' => 'linear-gradient(150deg, rgb(255 255 255 / 0.09) 0%, rgb(255 255 255 / 0) 55%)',
        ] : [
            'shadow-card' => "0 1px 2px 0 rgb({$shade} / 0.05), 0 12px 28px -16px rgb({$shade} / 0.18)",
            'shadow-lift' => "0 2px 4px 0 rgb({$shade} / 0.05), 0 28px 56px -24px rgb({$shade} / 0.28)",
            'shadow-glow' => "0 0 0 1px rgb({$primary} / 0.18), 0 20px 45px -22px rgb({$primary} / 0.5)",
            'veil' => 'linear-gradient(160deg, rgb(255 255 255 / 0.9) 0%, rgb(255 255 255 / 0) 45%)',
            'veil-strong' => 'linear-gradient(150deg, rgb(255 255 255 / 1) 0%, rgb(255 255 255 / 0) 55%)',
        ];
    }

    /**
     * Status colours and the soft surfaces they sit on.
     *
     * These are never taken from the user's palette: "over budget" has to stay
     * recognisable whatever colours somebody picks.
     *
     * @return array<string, string>
     */
    private static function statusTokens(string $card, bool $surfaceIsDark): array
    {
        $tokens = [];

        foreach (config('theme.status.'.($surfaceIsDark ? 'dark' : 'light')) as $name => $color) {
            // These carry words as often as they fill shapes, so each one is
            // held to 4.5:1 on the card. The built-in palettes already clear
            // it; a custom card colour is what this guards against.
            $color = Color::ensureContrast($color, $card, 4.5);

            $tokens[$name] = $color;
            $tokens["{$name}-foreground"] = Color::readableForeground($color);
            $tokens["{$name}-soft"] = Color::mix($card, $color, $surfaceIsDark ? 0.20 : 0.12);
        }

        return $tokens;
    }

    /**
     * @return array<string, string>
     */
    public static function tokensFor(?User $user, string $scheme): array
    {
        return self::tokens(self::baseColors($user, $scheme));
    }

    /**
     * Render the `:root` blocks for a user, ready to be inlined in <head>.
     *
     * Rendering server-side means the correct palette is present in the very
     * first paint: there is no flash of the wrong theme on page load.
     */
    public static function styleSheet(?User $user): string
    {
        $mode = $user?->theme_mode ?? ThemeMode::from(config('theme.default_mode'));

        if ($mode === ThemeMode::Custom) {
            return self::customBlock($user);
        }

        return self::sheet($mode, self::tokensFor($user, 'light'), self::tokensFor($user, 'dark'));
    }

    /**
     * The same blocks, for a theme nobody has saved yet.
     *
     * The onboarding screen previews a palette while it is still being chosen,
     * so it has no user record to read the choice back from. Only the presets
     * are offered there — a custom palette is built on the appearance screen,
     * where there is room for seven colour pickers and a contrast warning.
     */
    public static function previewSheet(ThemeMode $mode, string $preset): string
    {
        return self::sheet(
            $mode === ThemeMode::Custom ? ThemeMode::Light : $mode,
            self::tokens(self::preset($preset, 'light')),
            self::tokens(self::preset($preset, 'dark')),
        );
    }

    /**
     * @param  array<string, string>  $light
     * @param  array<string, string>  $dark
     */
    private static function sheet(ThemeMode $mode, array $light, array $dark): string
    {
        return match ($mode) {
            ThemeMode::Dark => self::block(':root', $dark, 'dark'),

            // Following the system means shipping both and letting the browser
            // choose, which is the only way to get it right on the first paint.
            ThemeMode::System => self::block(':root', $light, 'light')
                .'@media (prefers-color-scheme: dark){'
                .self::block(':root', $dark, 'dark')
                .'}',

            default => self::block(':root', $light, 'light'),
        };
    }

    /**
     * The colour a phone should tint its status bar with.
     *
     * Returned as either one fixed colour or a light/dark pair, mirroring the
     * user's theme mode: somebody following the system needs both, so the meta
     * tags can carry a media query and the bar keeps up when the phone flips
     * at sunset. Somebody who picked a side gets that side, always.
     *
     * @return array{fixed?: string, light?: string, dark?: string}
     */
    public static function browserThemeColors(?User $user): array
    {
        $mode = $user?->theme_mode ?? ThemeMode::from(config('theme.default_mode'));

        return match ($mode) {
            ThemeMode::Light => ['fixed' => self::tokensFor($user, 'light')['background']],
            ThemeMode::Dark => ['fixed' => self::tokensFor($user, 'dark')['background']],
            ThemeMode::Custom => ['fixed' => self::tokensFor($user, 'light')['background']],
            ThemeMode::System => [
                'light' => self::tokensFor($user, 'light')['background'],
                'dark' => self::tokensFor($user, 'dark')['background'],
            ],
        };
    }

    /**
     * The colour scheme a palette reads as, which decides whether the browser
     * paints scrollbars and form controls light or dark.
     */
    public static function schemeOf(array $tokens): string
    {
        return Color::isDark($tokens['background']) ? 'dark' : 'light';
    }

    private static function customBlock(?User $user): string
    {
        $tokens = self::tokensFor($user, 'light');

        return self::block(':root', $tokens, self::schemeOf($tokens));
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private static function block(string $selector, array $tokens, string $scheme): string
    {
        return $selector.'{'.self::declarations($tokens, $scheme).'}';
    }

    /**
     * The custom properties of one palette, without a selector around them.
     *
     * The landing page paints a small preview in each preset by putting
     * these on an element instead of on `:root`, so the preview recolours
     * while the page around it stays as it is.
     *
     * @param  array<string, string>  $tokens
     */
    public static function declarations(array $tokens, string $scheme): string
    {
        $declarations = "color-scheme:{$scheme};";

        foreach ($tokens as $name => $value) {
            $declarations .= "--color-{$name}:{$value};";
            $declarations .= "--rgb-{$name}:".Color::toRgbChannels($value).';';
        }

        foreach (self::effects($tokens) as $name => $value) {
            $declarations .= "--{$name}:{$value};";
        }

        return $declarations;
    }
}
