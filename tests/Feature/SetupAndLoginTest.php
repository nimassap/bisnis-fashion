<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SetupAndLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_install_sends_visitors_to_setup(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertRedirect('/setup');
        $this->get('/setup')->assertOk()->assertSee(__('auth.setup_title'));
    }

    public function test_setup_creates_owner_and_business_and_signs_in(): void
    {
        $this->post('/setup', [
            'name' => 'Rina',
            'email' => 'rina@example.test',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'working_name' => 'Proyek Linen',
            'collection_name' => 'Linen Senja',
            'target_launch_on' => now()->addMonths(3)->toDateString(),
        ])->assertRedirect('/dashboard');

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Proyek Linen', $user->business->working_name);

        $collection = $user->business->collections()->sole();
        $this->assertSame(1, $collection->number);
        $this->assertSame('Linen Senja', $collection->name);
        $this->assertNotNull($user->business->brandPhase);
        $this->assertSame(9, $collection->phases()->count());
    }

    public function test_setup_is_closed_once_an_owner_exists(): void
    {
        Business::factory()->create();

        $this->get('/setup')->assertRedirect('/login');

        $this->post('/setup', [
            'name' => 'Penyusup',
            'email' => 'lain@example.test',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'working_name' => 'Lain',
        ])->assertRedirect('/login');

        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function test_setup_rejects_weak_password(): void
    {
        $this->post('/setup', [
            'name' => 'Rina',
            'email' => 'rina@example.test',
            'password' => 'lemah',
            'password_confirmation' => 'lemah',
            'working_name' => 'Proyek Linen',
        ])->assertSessionHasErrors('password');

        $this->assertSame(0, User::count());
    }

    public function test_owner_can_log_in_and_out(): void
    {
        $business = Business::factory()->create();
        $owner = $business->owner;

        $this->get('/login')->assertOk();

        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($owner);
        $this->assertNotNull($owner->fresh()->last_login_at);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $owner = Business::factory()->create()->owner;

        $this->post('/login', ['email' => $owner->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
