<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Collections\CollectionShow;
use App\Livewire\Collections\CollectionsIndex;
use App\Models\Business;
use App\Models\Collection;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CollectionsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->owner = $this->business->owner;
        $this->actingAs($this->owner);
    }

    public function test_empty_list_invites_adding_a_collection(): void
    {
        $this->get('/collections')->assertOk()->assertSee(__('collections.empty'));
    }

    public function test_owner_can_add_a_collection(): void
    {
        Livewire::test(CollectionsIndex::class)
            ->call('openCreate')
            ->set('name', 'Linen Senja')
            ->set('targetLaunchOn', now()->addMonths(2)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $collection = $this->business->collections()->sole();
        $this->assertSame(1, $collection->number);
        $this->assertSame('Koleksi #1 · Linen Senja', $collection->label());
        $this->assertSame(9, $collection->phases()->count());
    }

    public function test_name_is_optional(): void
    {
        Livewire::test(CollectionsIndex::class)
            ->call('openCreate')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Koleksi #1', $this->business->collections()->sole()->label());
    }

    public function test_numbers_keep_counting_up(): void
    {
        $this->business->collections()->create();
        $second = $this->business->collections()->create();
        $this->business->collections()->create()->delete();

        $this->assertSame(2, $second->number);
        $this->assertSame(3, $this->business->collections()->create()->number);
    }

    public function test_numbers_are_per_business(): void
    {
        Collection::factory()->count(2)->create();

        $this->assertSame(1, $this->business->collections()->create()->number);
    }

    public function test_list_and_detail_show_progress(): void
    {
        $collection = $this->business->collections()->create(['name' => 'Linen Senja']);

        $this->get('/collections')->assertOk()
            ->assertSee('Linen Senja')
            ->assertSee('Buat Model');

        $this->get("/collections/{$collection->id}")->assertOk()
            ->assertSee('Linen Senja')
            ->assertSee('Sampling')
            ->assertSee('Launching');
    }

    public function test_owner_can_edit_a_collection(): void
    {
        $collection = $this->business->collections()->create(['name' => 'Linen Senja']);

        Livewire::test(CollectionShow::class, ['collection' => $collection->id])
            ->call('openEdit')
            ->set('name', 'Linen Senja Edisi Lebaran')
            ->set('notes', 'Warna sage dan krem.')
            ->call('save')
            ->assertHasNoErrors();

        $collection->refresh();
        $this->assertSame('Linen Senja Edisi Lebaran', $collection->name);
        $this->assertSame('Warna sage dan krem.', $collection->notes);
    }

    public function test_deleting_a_collection_removes_its_phases(): void
    {
        $collection = $this->business->collections()->create();
        $phaseIds = $collection->phases()->pluck('id');

        Livewire::test(CollectionShow::class, ['collection' => $collection->id])
            ->call('confirmDelete')
            ->call('delete')
            ->assertRedirect('/collections');

        $this->assertNull($collection->fresh());
        $this->assertSame(0, Phase::query()->whereIn('id', $phaseIds)->count());
        $this->assertNotNull($this->business->brandPhase()->first());
    }

    public function test_another_business_collection_cannot_be_opened(): void
    {
        $foreign = Collection::factory()->create();

        Livewire::test(CollectionShow::class, ['collection' => $foreign->id])->assertNotFound();
    }
}
