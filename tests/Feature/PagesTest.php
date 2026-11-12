<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Settings\BusinessPage;
use App\Livewire\Settings\ProfilePage;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PagesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = Business::factory()->create([
            'working_name' => 'Proyek Linen',
            'starting_capital' => '25000000',
        ])->owner;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'brand' => ['/brand'],
            'collections' => ['/collections'],
            'profile' => ['/settings/profile'],
            'business' => ['/settings/business'],
            'appearance' => ['/settings/appearance'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_for_owner(string $url): void
    {
        $this->actingAs($this->owner)->get($url)->assertOk();
    }

    #[DataProvider('pages')]
    public function test_page_requires_login(string $url): void
    {
        $this->get($url)->assertRedirect('/login');
    }

    public function test_dashboard_shows_business_brand_phase_and_collections(): void
    {
        $this->owner->business->collections()->create(['name' => 'Linen Senja']);

        $this->actingAs($this->owner)->get('/dashboard')
            ->assertSee('Proyek Linen')
            ->assertSee('Rp 25.000.000')
            ->assertSee(__('navigation.brand'))
            ->assertSee('Koleksi #1 · Linen Senja')
            ->assertSee('Fase 2 dari 6 · Desain &amp; Material', false);
    }

    public function test_brand_name_replaces_working_name_once_set(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(BusinessPage::class)
            ->set('brandName', 'Serat Senja')
            ->set('startingCapital', '30.000.000')
            ->call('save')
            ->assertHasNoErrors();

        $business = $this->owner->business->fresh();
        $this->assertSame('Serat Senja', $business->displayName());
        $this->assertSame('30000000.00', $business->starting_capital);

        $this->get('/dashboard')->assertSee('Serat Senja');
    }

    public function test_business_requires_working_name(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(BusinessPage::class)
            ->set('workingName', '')
            ->call('save')
            ->assertHasErrors('workingName');
    }

    public function test_owner_can_change_password(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(ProfilePage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'BaruSekali9')
            ->set('newPassword_confirmation', 'BaruSekali9')
            ->call('savePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('BaruSekali9', $this->owner->fresh()->password));
    }

    public function test_password_change_needs_current_password(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(ProfilePage::class)
            ->set('currentPassword', 'salah')
            ->set('newPassword', 'BaruSekali9')
            ->set('newPassword_confirmation', 'BaruSekali9')
            ->call('savePassword')
            ->assertHasErrors('currentPassword');
    }
}
