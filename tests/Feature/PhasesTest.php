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
use App\Services\StepRequirements;
use App\Services\TaskAgenda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * How steps open, close and reopen. The data each step asks for is tested
 * per step elsewhere; here requirements are switched off unless a test
 * says otherwise.
 */
final class PhasesTest extends TestCase
{
    use RefreshDatabase;

    private const STEPS = ['model', 'maker', 'fabric', 'trims', 'sampling', 'costing', 'production', 'prelaunch', 'launch'];

    private Business $business;

    private User $owner;

    private Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        StepRequirements::fakeUsing(fn () => []);

        $this->business = Business::factory()->create();
        $this->owner = $this->business->owner;
        $this->collection = $this->business->collections()->create(['name' => 'Fiore']);
    }

    private function setupStep(): Phase
    {
        return $this->business->brandPhase()->firstOrFail();
    }

    private function step(string $key, ?Collection $collection = null): Phase
    {
        return ($collection ?? $this->collection)->phases()->where('key', $key)->firstOrFail();
    }

    private function close(Phase $phase): void
    {
        $phase->forceFill(['completed_at' => now()])->save();
    }

    private function show(?Phase $phase = null)
    {
        $phase ??= $this->step('model');

        return $phase->isBrandPhase()
            ? Livewire::test(PhaseShow::class)
            : Livewire::test(PhaseShow::class, ['collection' => $phase->collection_id, 'key' => $phase->key]);
    }

    private function task(Phase $phase, string $title = 'Task', bool $gate = false): PhaseTask
    {
        return $phase->tasks()->create(['title' => $title, 'is_gate' => $gate, 'position' => 99]);
    }

    // ── Seeding ────────────────────────────────────────────────────────

    public function test_business_gets_setup_at_position_zero(): void
    {
        $setup = $this->setupStep();

        $this->assertSame('setup', $setup->key);
        $this->assertSame(0, $setup->position);
        $this->assertSame('Setup brand', $setup->positionLabel());
        $this->assertSame(1, $this->business->allPhases()->whereNull('collection_id')->count());
    }

    public function test_collection_gets_nine_steps_in_order(): void
    {
        $phases = $this->collection->phases()->get();

        $this->assertSame(self::STEPS, $phases->pluck('key')->all());
        $this->assertSame(range(1, 9), $phases->pluck('position')->all());
        $this->assertSame('Step 3 dari 9', $this->step('fabric')->positionLabel());
    }

    public function test_only_true_checklist_items_are_seeded(): void
    {
        $this->assertSame(0, $this->step('model')->tasks()->count());
        $this->assertSame(3, $this->step('prelaunch')->tasks()->count());
    }

    public function test_seeding_again_keeps_owner_edits(): void
    {
        $task = $this->step('prelaunch')->tasks()->first();
        $task->update(['title' => 'Judul milik owner']);

        app(SeedPhases::class)->forCollection($this->collection);
        app(SeedPhases::class)->forBusiness($this->business);

        $this->assertSame(9, $this->collection->phases()->count());
        $this->assertSame('Judul milik owner', $task->fresh()->title);
    }

    // ── Pages ──────────────────────────────────────────────────────────

    public function test_setup_and_step_pages_render(): void
    {
        StepRequirements::fakeUsing(null);
        $this->actingAs($this->owner);

        $this->get('/brand')->assertOk()->assertSee('Setup Brand')->assertSee(__('steps.setup.target_title'));

        foreach (self::STEPS as $key) {
            $this->get("/collections/{$this->collection->id}/phases/{$key}")->assertOk()->assertSee(__("phases.{$key}.name"));
        }
    }

    public function test_unknown_step_or_collection_is_not_found(): void
    {
        $this->actingAs($this->owner);

        $this->get("/collections/{$this->collection->id}/phases/setup")->assertNotFound();
        $this->get("/collections/{$this->collection->id}/phases/nope")->assertNotFound();
        $this->get('/collections/999999/phases/model')->assertNotFound();
        $this->get('/collections/'.Collection::factory()->create()->id.'/phases/model')->assertNotFound();
    }

    // ── Status and closing ─────────────────────────────────────────────

    public function test_setup_and_step_one_are_both_active_at_the_start(): void
    {
        $board = PhaseBoard::forCollection($this->collection);

        $this->assertSame('setup', $board->active()->key);
        $this->assertSame('model', $board->current()->key);
        $this->assertSame(PhaseBoard::UPCOMING, $board->status($board->find('maker')));
    }

    public function test_step_one_cannot_close_before_setup(): void
    {
        $this->actingAs($this->owner);

        $this->show()->call('completePhase');

        $this->assertFalse($this->step('model')->isCompleted());
    }

    public function test_steps_close_in_order(): void
    {
        $this->actingAs($this->owner);
        $this->close($this->setupStep());

        $this->show($this->step('maker'))->call('completePhase');
        $this->assertFalse($this->step('maker')->isCompleted());

        $this->show()->call('completePhase');
        $this->assertTrue($this->step('model')->isCompleted());
        $this->assertSame('maker', PhaseBoard::forCollection($this->collection)->current()->key);
    }

    public function test_unmet_requirements_block_closing(): void
    {
        StepRequirements::fakeUsing(fn () => [(object) ['label' => 'Isi sesuatu', 'met' => false]]);
        $this->actingAs($this->owner);

        $this->show($this->setupStep())
            ->assertSee('Isi sesuatu')
            ->call('completePhase');

        $this->assertFalse($this->setupStep()->fresh()->isCompleted());
    }

    public function test_open_gate_task_blocks_closing(): void
    {
        $this->actingAs($this->owner);
        $this->task($this->setupStep(), 'Wajib', gate: true);

        $this->show($this->setupStep())->call('completePhase');

        $this->assertFalse($this->setupStep()->fresh()->isCompleted());
    }

    public function test_collection_is_finished_when_launch_closes(): void
    {
        $this->close($this->setupStep());
        foreach (self::STEPS as $key) {
            $this->close($this->step($key));
        }

        $this->assertTrue(PhaseBoard::forCollection($this->collection)->isFinished());
    }

    public function test_progress_counts_requirements_and_tasks(): void
    {
        StepRequirements::fakeUsing(fn () => [(object) ['label' => 'A', 'met' => true], (object) ['label' => 'B', 'met' => false]]);
        $phase = $this->step('prelaunch');
        $phase->tasks()->first()->forceFill(['completed_at' => now()])->save();

        // 1 of 2 requirements + 1 of 3 tasks
        $this->assertSame(['done' => 2, 'total' => 5], $phase->fresh()->progressCounts());
        $this->assertSame(40, $phase->fresh()->progress());
    }

    public function test_closed_step_is_read_only(): void
    {
        $this->actingAs($this->owner);
        $task = $this->task($this->setupStep());
        $this->close($this->setupStep());

        $this->show($this->setupStep())->call('toggleTask', $task->id)->assertForbidden();

        $this->assertFalse($task->fresh()->isDone());
    }

    public function test_only_the_latest_closed_step_can_be_reopened(): void
    {
        $this->actingAs($this->owner);
        $this->close($this->setupStep());
        $this->close($this->step('model'));
        $this->close($this->step('maker'));

        $this->show($this->step('model'))->call('reopenPhase');
        $this->assertTrue($this->step('model')->isCompleted());

        $this->show($this->step('maker'))->call('reopenPhase');
        $this->assertFalse($this->step('maker')->isCompleted());
    }

    public function test_setup_stays_closed_once_a_collection_has_moved_on(): void
    {
        $this->actingAs($this->owner);
        $this->close($this->setupStep());
        $this->close($this->step('model'));

        $this->show($this->setupStep())->call('reopenPhase');

        $this->assertTrue($this->setupStep()->fresh()->isCompleted());
    }

    // ── Checklist and decisions ────────────────────────────────────────

    public function test_owner_can_add_tick_and_delete_a_checklist_item(): void
    {
        $this->actingAs($this->owner);

        $this->show()
            ->call('openTaskCreate')
            ->set('taskTitle', 'Survey pasar Tanah Abang')
            ->set('taskIsGate', true)
            ->call('saveTask')
            ->assertHasNoErrors();

        $task = $this->step('model')->tasks()->sole();
        $this->assertTrue($task->is_gate);

        $this->show()->call('toggleTask', $task->id);
        $this->assertTrue($task->fresh()->isDone());

        $this->show()->call('confirmDeleteTask', $task->id)->call('deleteTask');
        $this->assertNull($task->fresh());
    }

    public function test_task_from_another_collection_cannot_be_touched(): void
    {
        $other = $this->business->collections()->create();
        $foreign = $this->task($this->step('model', $other));
        $this->actingAs($this->owner);

        $this->show()->call('toggleTask', $foreign->id)->assertNotFound();
    }

    public function test_owner_can_record_a_decision(): void
    {
        $this->actingAs($this->owner);

        $this->show()
            ->call('openDecisionCreate')
            ->set('decisionTitle', 'Tema Fiore: bunga pastel')
            ->call('saveDecision')
            ->assertHasNoErrors();

        $this->assertSame('Tema Fiore: bunga pastel', $this->step('model')->decisions()->sole()->title);
    }

    public function test_agenda_covers_setup_and_every_collection(): void
    {
        $late = $this->task($this->setupStep(), 'Telat setup');
        $late->update(['due_on' => today()->subDays(2)]);
        $soon = $this->task($this->step('maker'), 'Segera');
        $soon->update(['due_on' => today()->addDay()]);

        $agenda = new TaskAgenda($this->business);

        $this->assertSame([$late->id], $agenda->overdue()->pluck('id')->all());
        $this->assertSame([$soon->id], $agenda->upcoming()->pluck('id')->all());
    }
}
