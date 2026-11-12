<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Phases\SeedPhases;
use App\Livewire\Phases\PhaseShow;
use App\Models\Business;
use App\Models\Collection;
use App\Models\Phase;
use App\Models\PhaseTask;
use App\Models\User;
use App\Services\PhaseBoard;
use App\Services\TaskAgenda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class PhasesTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->owner = $this->business->owner;
        $this->collection = $this->business->collections()->create(['name' => 'Linen Senja']);
    }

    private function brand(): Phase
    {
        return $this->business->brandPhase()->firstOrFail();
    }

    private function phase(string $key, ?Collection $collection = null): Phase
    {
        return ($collection ?? $this->collection)->phases()->where('key', $key)->firstOrFail();
    }

    private function close(Phase $phase): void
    {
        $phase->tasks()->where('is_gate', true)->update(['completed_at' => now()]);
        $phase->forceFill(['completed_at' => now()])->save();
    }

    private function finishGates(Phase $phase): void
    {
        $phase->tasks()->where('is_gate', true)->update(['completed_at' => now()]);
    }

    private function show(?Phase $phase = null)
    {
        $phase ??= $this->phase('design');

        return $phase->isBrandPhase()
            ? Livewire::test(PhaseShow::class)
            : Livewire::test(PhaseShow::class, ['collection' => $phase->collection_id, 'key' => $phase->key]);
    }

    // ── Seeding ────────────────────────────────────────────────────────

    public function test_business_gets_one_brand_phase(): void
    {
        $brand = $this->brand();

        $this->assertSame('foundation', $brand->key);
        $this->assertSame(1, $brand->position);
        $this->assertSame(count(config('phases.foundation.tasks')), $brand->tasks()->count());
        $this->assertSame(1, $this->business->allPhases()->whereNull('collection_id')->count());
    }

    public function test_collection_gets_its_own_five_phases(): void
    {
        $phases = $this->collection->phases()->get();

        $this->assertSame(['design', 'sourcing', 'costing', 'production', 'launch'], $phases->pluck('key')->all());
        $this->assertSame([2, 3, 4, 5, 6], $phases->pluck('position')->all());
        $this->assertTrue($this->phase('design')->tasks()->where('title', 'Tentukan model cewek dan cowok')->sole()->is_gate);
    }

    public function test_each_collection_has_independent_checklists(): void
    {
        $second = $this->business->collections()->create(['name' => 'Batik Pagi']);

        $this->assertSame(2, $second->number);

        $task = $this->phase('design')->tasks()->first();
        $task->update(['completed_at' => now()]);

        $this->assertFalse($this->phase('design', $second)->tasks()->first()->isDone());
    }

    public function test_seeding_again_keeps_owner_edits(): void
    {
        $task = $this->phase('design')->tasks()->first();
        $task->update(['title' => 'Judul milik owner']);

        app(SeedPhases::class)->forCollection($this->collection);
        app(SeedPhases::class)->forBusiness($this->business);

        $this->assertSame(5, $this->collection->phases()->count());
        $this->assertSame(1, $this->business->allPhases()->whereNull('collection_id')->count());
        $this->assertSame('Judul milik owner', $task->fresh()->title);
    }

    // ── Pages ──────────────────────────────────────────────────────────

    public function test_brand_and_collection_phase_pages_render(): void
    {
        $this->actingAs($this->owner);

        $this->get('/brand')->assertOk()->assertSee('Fondasi Brand')->assertSee('Putuskan nama brand final');

        $this->get("/collections/{$this->collection->id}/phases/design")->assertOk()
            ->assertSee('Desain &amp; Material', false)
            ->assertSee('Tentukan model cewek dan cowok')
            ->assertSee('Koleksi #1 · Linen Senja');
    }

    public function test_unknown_phase_or_collection_is_not_found(): void
    {
        $this->actingAs($this->owner);

        $this->get("/collections/{$this->collection->id}/phases/foundation")->assertNotFound();
        $this->get("/collections/{$this->collection->id}/phases/nope")->assertNotFound();
        $this->get('/collections/999999/phases/design')->assertNotFound();
    }

    public function test_another_business_collection_is_not_found(): void
    {
        $foreign = Collection::factory()->create();

        $this->actingAs($this->owner);

        $this->get("/collections/{$foreign->id}")->assertNotFound();
        $this->get("/collections/{$foreign->id}/phases/design")->assertNotFound();
    }

    // ── Status ─────────────────────────────────────────────────────────

    public function test_brand_and_design_are_both_active_at_the_start(): void
    {
        $board = PhaseBoard::forCollection($this->collection);

        $this->assertSame('foundation', $board->active()->key);
        $this->assertSame('design', $board->current()->key);
        $this->assertSame(PhaseBoard::ACTIVE, $board->status($board->find('foundation')));
        $this->assertSame(PhaseBoard::ACTIVE, $board->status($board->find('design')));
        $this->assertSame(PhaseBoard::UPCOMING, $board->status($board->find('sourcing')));
    }

    public function test_current_phase_moves_on_as_phases_close(): void
    {
        $this->close($this->brand());
        $this->close($this->phase('design'));

        $board = PhaseBoard::forCollection($this->collection);

        $this->assertSame('sourcing', $board->active()->key);
        $this->assertSame('sourcing', $board->current()->key);
        $this->assertSame(PhaseBoard::UPCOMING, $board->status($board->find('costing')));
    }

    public function test_collection_is_finished_when_launch_closes(): void
    {
        $this->close($this->brand());
        foreach (['design', 'sourcing', 'costing', 'production', 'launch'] as $key) {
            $this->close($this->phase($key));
        }

        $this->assertTrue(PhaseBoard::forCollection($this->collection)->isFinished());
    }

    // ── Tasks ──────────────────────────────────────────────────────────

    public function test_owner_can_tick_a_task(): void
    {
        $this->actingAs($this->owner);
        $task = $this->phase('design')->tasks()->first();

        $this->show()->call('toggleTask', $task->id);
        $this->assertTrue($task->fresh()->isDone());

        $this->show()->call('toggleTask', $task->id);
        $this->assertFalse($task->fresh()->isDone());
    }

    public function test_brand_tasks_can_be_ticked(): void
    {
        $this->actingAs($this->owner);
        $task = $this->brand()->tasks()->first();

        $this->show($this->brand())->call('toggleTask', $task->id);

        $this->assertTrue($task->fresh()->isDone());
    }

    public function test_tasks_in_a_later_phase_can_be_worked_on_early(): void
    {
        $this->actingAs($this->owner);
        $task = $this->phase('costing')->tasks()->first();

        $this->show($this->phase('costing'))->call('toggleTask', $task->id);

        $this->assertTrue($task->fresh()->isDone());
    }

    public function test_owner_can_add_edit_and_delete_a_task(): void
    {
        $this->actingAs($this->owner);
        $phase = $this->phase('sourcing');
        $before = $phase->tasks()->count();

        $this->show($phase)
            ->call('openTaskCreate')
            ->set('taskTitle', 'Survey konveksi di Bandung')
            ->set('taskDueOn', now()->addWeek()->toDateString())
            ->set('taskIsGate', true)
            ->call('saveTask')
            ->assertHasNoErrors();

        $task = $phase->tasks()->where('title', 'Survey konveksi di Bandung')->sole();
        $this->assertTrue($task->is_gate);
        $this->assertSame($before + 1, $task->position);

        $this->show($phase)
            ->call('openTaskEdit', $task->id)
            ->set('taskTitle', 'Survey konveksi Bandung & Solo')
            ->call('saveTask');
        $this->assertSame('Survey konveksi Bandung & Solo', $task->fresh()->title);

        $this->show($phase)
            ->call('confirmDeleteTask', $task->id)
            ->call('deleteTask');
        $this->assertNull($task->fresh());
    }

    public function test_task_title_is_required(): void
    {
        $this->actingAs($this->owner);

        $this->show()
            ->call('openTaskCreate')
            ->call('saveTask')
            ->assertHasErrors('taskTitle');
    }

    public function test_tasks_can_be_reordered(): void
    {
        $this->actingAs($this->owner);
        $phase = $this->phase('costing');
        [$first, $second] = $phase->tasks()->take(2)->get()->all();

        $this->show($phase)->call('moveTask', $second->id, 'up');

        $this->assertSame([$second->id, $first->id], $phase->tasks()->take(2)->pluck('id')->all());
    }

    public function test_task_from_another_collection_cannot_be_touched(): void
    {
        $other = $this->business->collections()->create();
        $otherTask = $this->phase('design', $other)->tasks()->first();

        $this->actingAs($this->owner);

        $this->show()->call('toggleTask', $otherTask->id)->assertNotFound();

        $this->assertFalse($otherTask->fresh()->isDone());
    }

    // ── Closing and reopening ──────────────────────────────────────────

    public function test_phase_cannot_close_with_open_gates(): void
    {
        $this->actingAs($this->owner);

        $this->show($this->brand())->call('completePhase');

        $this->assertFalse($this->brand()->fresh()->isCompleted());
    }

    public function test_design_cannot_close_before_the_brand_phase(): void
    {
        $this->actingAs($this->owner);
        $this->finishGates($this->phase('design'));

        $this->show()->call('completePhase');

        $this->assertFalse($this->phase('design')->isCompleted());
    }

    public function test_design_closes_after_brand_and_gates_are_done(): void
    {
        $this->actingAs($this->owner);
        $this->close($this->brand());
        $this->finishGates($this->phase('design'));

        $this->show()->call('completePhase');

        $this->assertTrue($this->phase('design')->isCompleted());
        $this->assertSame('sourcing', PhaseBoard::forCollection($this->collection)->current()->key);
    }

    public function test_non_gate_tasks_do_not_block_closing(): void
    {
        $this->actingAs($this->owner);
        $this->finishGates($this->brand());

        $this->show($this->brand())->call('completePhase');

        $this->assertTrue($this->brand()->fresh()->isCompleted());
    }

    public function test_closed_phase_is_read_only(): void
    {
        $this->actingAs($this->owner);
        $this->close($this->brand());
        $task = $this->brand()->tasks()->whereNull('completed_at')->first();

        $this->show($this->brand())->call('toggleTask', $task->id)->assertForbidden();

        $this->assertFalse($task->fresh()->isDone());
    }

    public function test_only_the_latest_closed_phase_of_a_collection_can_be_reopened(): void
    {
        $this->actingAs($this->owner);
        $this->close($this->brand());
        $this->close($this->phase('design'));
        $this->close($this->phase('sourcing'));

        $this->show($this->phase('design'))->call('reopenPhase');
        $this->assertTrue($this->phase('design')->isCompleted());

        $this->show($this->phase('sourcing'))->call('reopenPhase');
        $this->assertFalse($this->phase('sourcing')->isCompleted());
    }

    public function test_brand_phase_stays_closed_once_a_collection_has_moved_on(): void
    {
        $this->actingAs($this->owner);
        $this->close($this->brand());

        $this->show($this->brand())->call('reopenPhase');
        $this->assertFalse($this->brand()->fresh()->isCompleted());

        $this->close($this->brand());
        $this->close($this->phase('design'));

        $this->show($this->brand())->call('reopenPhase');
        $this->assertTrue($this->brand()->fresh()->isCompleted());
    }

    // ── Decisions ──────────────────────────────────────────────────────

    public function test_owner_can_record_a_decision(): void
    {
        $this->actingAs($this->owner);

        $this->show()
            ->call('openDecisionCreate')
            ->set('decisionTitle', 'Pakai linen dari Supplier B')
            ->set('decisionReason', 'Supplier A susut 6%.')
            ->call('saveDecision')
            ->assertHasNoErrors();

        $decision = $this->phase('design')->decisions()->sole();
        $this->assertSame('Pakai linen dari Supplier B', $decision->title);
        $this->assertTrue($decision->decided_on->isToday());

        $this->get("/collections/{$this->collection->id}/phases/design")->assertSee('Pakai linen dari Supplier B');
    }

    // ── Agenda ─────────────────────────────────────────────────────────

    public function test_agenda_covers_brand_and_every_collection(): void
    {
        $other = $this->business->collections()->create();

        $brandTask = $this->brand()->tasks()->first();
        $brandTask->update(['due_on' => today()->subDays(3)]);
        $collectionTask = $this->phase('design', $other)->tasks()->first();
        $collectionTask->update(['due_on' => today()->subDay()]);
        $soonTask = $this->phase('sourcing')->tasks()->first();
        $soonTask->update(['due_on' => today()->addDays(2)]);

        $foreignTask = Collection::factory()->create()->phases()->first()->tasks()->first();
        $foreignTask->update(['title' => 'Task bisnis lain', 'due_on' => today()->subDays(5)]);

        $agenda = new TaskAgenda($this->business);

        $this->assertSame(2, $agenda->overdueCount());
        $this->assertSame([$brandTask->id, $collectionTask->id], $agenda->overdue()->pluck('id')->all());
        $this->assertSame([$soonTask->id], $agenda->upcoming()->pluck('id')->all());

        $this->actingAs($this->owner)->get('/dashboard')
            ->assertSee($brandTask->title)
            ->assertSee($soonTask->title)
            ->assertDontSee($foreignTask->title);
    }

    public function test_task_due_today_is_not_overdue(): void
    {
        $this->assertFalse((new PhaseTask(['due_on' => today()]))->isOverdue());
    }
}
