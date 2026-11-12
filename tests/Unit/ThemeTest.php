<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ThemeMode;
use App\Models\User;
use App\Support\Color;
use App\Support\ThemePalette;
use Tests\TestCase;

final class ThemeTest extends TestCase
{
    public function test_a_hex_colour_is_read_and_normalised(): void
    {
        $this->assertSame([37, 99, 235], Color::toRgb('#2563eb'));
        $this->assertSame([255, 255, 255], Color::toRgb('fff'));
        $this->assertSame('#2563eb', Color::normalizeHex('#2563EB'));
        $this->assertSame('#ffffff', Color::normalizeHex('fff'));
    }

    public function test_invalid_colours_are_recognised(): void
    {
        $this->assertTrue(Color::isValidHex('#abc'));
        $this->assertTrue(Color::isValidHex('112233'));
        $this->assertFalse(Color::isValidHex('bukan-warna'));
        $this->assertFalse(Color::isValidHex('#12345'));
    }

    public function test_contrast_is_measured_the_way_wcag_defines_it(): void
    {
        // Black on white is the maximum possible ratio.
        $this->assertEqualsWithDelta(21.0, Color::contrastRatio('#000000', '#ffffff'), 0.01);
        $this->assertEqualsWithDelta(1.0, Color::contrastRatio('#123456', '#123456'), 0.01);
    }

    public function test_text_is_paired_with_a_readable_colour(): void
    {
        $this->assertSame('#ffffff', Color::readableForeground('#1f2937'));
        $this->assertSame('#111827', Color::readableForeground('#fef3c7'));
    }

    public function test_dark_surfaces_are_recognised(): void
    {
        $this->assertTrue(Color::isDark('#0b1120'));
        $this->assertFalse(Color::isDark('#f6f7f9'));
    }

    public function test_colours_can_be_blended(): void
    {
        $this->assertSame('#808080', Color::mix('#000000', '#ffffff', 0.5));
        $this->assertSame('#000000', Color::mix('#000000', '#ffffff', 0));
        $this->assertSame('#ffffff', Color::mix('#000000', '#ffffff', 1));
    }

    public function test_seven_base_colours_expand_into_the_full_token_set(): void
    {
        $tokens = ThemePalette::tokens(ThemePalette::preset('blue', 'light'));

        foreach (['primary', 'primary-foreground', 'background', 'foreground', 'card', 'border', 'muted-foreground', 'ring'] as $token) {
            $this->assertArrayHasKey($token, $tokens);
            $this->assertTrue(Color::isValidHex($tokens[$token]), "The {$token} token is not a colour.");
        }
    }

    public function test_status_colours_are_present_whatever_the_palette(): void
    {
        $tokens = ThemePalette::tokens(ThemePalette::preset('monochrome', 'light'));

        foreach (['success', 'warning', 'danger', 'info'] as $status) {
            $this->assertSame(config("theme.status.light.{$status}"), $tokens[$status]);
            $this->assertArrayHasKey("{$status}-soft", $tokens);
        }
    }

    public function test_a_dark_palette_gets_the_dark_status_colours(): void
    {
        $tokens = ThemePalette::tokens(ThemePalette::preset('blue', 'dark'));

        $this->assertSame(config('theme.status.dark.danger'), $tokens['danger']);
    }

    public function test_a_button_label_always_contrasts_with_its_button(): void
    {
        foreach (array_keys(ThemePalette::presets()) as $preset) {
            foreach (['light', 'dark'] as $scheme) {
                $tokens = ThemePalette::tokens(ThemePalette::preset($preset, $scheme));

                $this->assertGreaterThanOrEqual(
                    4.5,
                    Color::contrastRatio($tokens['primary'], $tokens['primary-foreground']),
                    "The {$preset} {$scheme} primary button is hard to read.",
                );
            }
        }
    }

    public function test_every_preset_keeps_its_body_text_readable(): void
    {
        foreach (array_keys(ThemePalette::presets()) as $preset) {
            foreach (['light', 'dark'] as $scheme) {
                $tokens = ThemePalette::tokens(ThemePalette::preset($preset, $scheme));

                $this->assertGreaterThanOrEqual(
                    (float) config('theme.minimum_contrast_ratio'),
                    Color::contrastRatio($tokens['foreground'], $tokens['background']),
                    "The {$preset} {$scheme} body text is hard to read.",
                );
            }
        }
    }

    public function test_the_stylesheet_follows_the_users_mode(): void
    {
        $light = User::factory()->make(['theme_mode' => ThemeMode::Light, 'theme_preset' => 'blue']);
        $system = User::factory()->make(['theme_mode' => ThemeMode::System, 'theme_preset' => 'blue']);

        $this->assertStringContainsString('color-scheme:light', ThemePalette::styleSheet($light));
        $this->assertStringNotContainsString('prefers-color-scheme', ThemePalette::styleSheet($light));

        // "System" ships both palettes and lets the browser choose.
        $this->assertStringContainsString('prefers-color-scheme: dark', ThemePalette::styleSheet($system));
    }

    public function test_a_palette_can_be_previewed_before_anybody_saves_it(): void
    {
        // The onboarding screen paints a theme while it is still being chosen,
        // so it has no user record to read the choice back out of.
        $system = ThemePalette::previewSheet(ThemeMode::System, 'forest');

        $this->assertStringContainsString(config('theme.presets.forest.light.primary'), $system);
        $this->assertStringContainsString('prefers-color-scheme: dark', $system);
        $this->assertStringContainsString(config('theme.presets.forest.dark.primary'), $system);

        $dark = ThemePalette::previewSheet(ThemeMode::Dark, 'ruby');

        $this->assertStringContainsString('color-scheme:dark', $dark);
        $this->assertStringNotContainsString('prefers-color-scheme', $dark);
    }

    public function test_previewing_a_custom_theme_shows_the_preset_it_starts_from(): void
    {
        // Custom is not offered where previews are used, and a theme nobody has
        // saved has no custom colours to show in the first place.
        $this->assertSame(
            ThemePalette::previewSheet(ThemeMode::Light, 'sand'),
            ThemePalette::previewSheet(ThemeMode::Custom, 'sand'),
        );
    }

    public function test_a_custom_palette_reaches_the_stylesheet(): void
    {
        $user = User::factory()->make([
            'theme_mode' => ThemeMode::Custom,
            'theme_preset' => 'blue',
            'theme_colors' => ['primary' => '#ff0000'],
        ]);

        $this->assertStringContainsString('--color-primary:#ff0000', ThemePalette::styleSheet($user));
    }

    public function test_a_nonsense_custom_colour_falls_back_to_the_preset(): void
    {
        $user = User::factory()->make([
            'theme_mode' => ThemeMode::Custom,
            'theme_preset' => 'emerald',
            'theme_colors' => ['primary' => 'javascript:alert(1)'],
        ]);

        $colors = ThemePalette::baseColors($user, 'light');

        $this->assertSame(config('theme.presets.emerald.light.primary'), $colors['primary']);
    }

    public function test_a_guest_still_gets_a_usable_palette(): void
    {
        $stylesheet = ThemePalette::styleSheet(null);

        $this->assertStringContainsString('--color-primary:', $stylesheet);
        $this->assertStringContainsString('--color-background:', $stylesheet);
    }
}
