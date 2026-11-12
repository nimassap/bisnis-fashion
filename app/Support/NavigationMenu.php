<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * The single definition of the app's navigation.
 *
 * The sidebar, the bottom bar and the "more" sheet all read from here. An
 * entry whose route does not exist yet is skipped, so a module shows up in
 * the menu the moment its route is registered.
 */
final class NavigationMenu
{
    /**
     * @return Collection<int, object>
     */
    public static function primary(): Collection
    {
        return collect([
            ['dashboard', 'navigation.dashboard', 'home'],
            ['brand.show', 'navigation.brand', 'sparkles'],
            ['collections.index', 'navigation.collections', 'shirt'],
            ['products.index', 'navigation.products', 'users'],
            ['fabrics.index', 'navigation.fabrics', 'layers'],
            ['vendors.index', 'navigation.vendors', 'briefcase'],
            ['pricing.index', 'navigation.pricing', 'hand-coins'],
            ['cashbook.index', 'navigation.cashbook', 'wallet'],
            ['payments.index', 'navigation.payments', 'receipt'],
            ['promotions.index', 'navigation.promotions', 'sparkles'],
            ['sales.index', 'navigation.sales', 'chart-bar'],
        ])
            ->filter(fn (array $entry) => Route::has($entry[0]))
            ->map(fn (array $entry) => self::item(...$entry))
            ->values();
    }

    /**
     * Up to four destinations beside the "more" button on a phone.
     *
     * @return Collection<int, object>
     */
    public static function bottomBar(): Collection
    {
        return self::primary()->take(4);
    }

    /**
     * Everything that is not in the bottom bar.
     *
     * @return Collection<int, object>
     */
    public static function overflow(): Collection
    {
        return self::primary()
            ->slice(4)
            ->push(self::item('settings.profile', 'navigation.settings', 'settings'))
            ->values();
    }

    /**
     * @return Collection<int, object>
     */
    public static function settings(): Collection
    {
        return collect([
            self::item('settings.profile', 'navigation.profile', 'user', exact: true),
            self::item('settings.business', 'navigation.business', 'briefcase', exact: true),
            self::item('settings.appearance', 'navigation.appearance', 'palette', exact: true),
        ]);
    }

    /**
     * A menu entry. Unless exact, it stays highlighted on every page of its
     * section: "products.index" is active on "products.show" too.
     */
    private static function item(string $route, string $label, string $icon, bool $exact = false): object
    {
        $pattern = ! $exact && str_contains($route, '.') ? strstr($route, '.', true).'.*' : $route;

        return (object) [
            'key' => str_replace('.', '-', $route),
            'route' => $route,
            'url' => route($route),
            'label' => __($label),
            'icon' => $icon,
            'active' => request()->routeIs($route, $pattern),
        ];
    }
}
