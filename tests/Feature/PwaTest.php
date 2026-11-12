<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_public_and_installable(): void
    {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJson([
                'name' => 'Bisnis Fashion',
                'short_name' => 'Fashion',
                'start_url' => '/dashboard',
                'display' => 'standalone',
                'lang' => 'id',
            ]);

        $icons = collect($response->json('icons'));
        $this->assertTrue($icons->contains('sizes', '192x192'));
        $this->assertTrue($icons->contains('sizes', '512x512'));
        $this->assertTrue($icons->contains('purpose', 'maskable'));

        foreach ($icons as $icon) {
            $this->assertFileExists(public_path(parse_url($icon['src'], PHP_URL_PATH)));
        }
    }

    public function test_service_worker_offline_page_and_icons_ship(): void
    {
        foreach (['service-worker.js', 'offline.html', 'favicon.ico', 'icons/apple-touch-icon.png'] as $file) {
            $this->assertFileExists(public_path($file));
        }

        $worker = file_get_contents(public_path('service-worker.js'));
        $this->assertStringContainsString('bisnis-fashion-', $worker);
        // Pages are never served from cache: only build assets and icons are.
        $this->assertStringContainsString("const IMMUTABLE = ['/build/', '/icons/']", $worker);
    }

    public function test_pages_link_the_manifest_and_offer_install(): void
    {
        Business::factory()->create();

        $this->get('/login')
            ->assertSee('rel="manifest"', false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee(__('install.action'));

        $owner = Business::first()->owner;
        $this->actingAs($owner)->get('/dashboard')
            ->assertSee('rel="manifest"', false)
            ->assertSee('name="theme-color"', false)
            ->assertSee(__('install.banner_title'))
            ->assertSee(__('common.update_banner_title'));
    }
}
