<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\VendorType;
use App\Livewire\Setup\BrandSetup;
use App\Models\Business;
use App\Models\User;
use App\Models\Vendor;
use App\Services\StepRequirements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class BrandSetupTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->business = Business::factory()->create(['starting_capital' => null]);
        $this->owner = $this->business->owner;
        $this->actingAs($this->owner);
    }

    /**
     * @return array<string, bool>
     */
    private function requirements(): array
    {
        return collect(StepRequirements::for($this->business->brandPhase()->first()))
            ->mapWithKeys(fn ($r) => [$r->label => $r->met])->all();
    }

    public function test_requirements_start_unmet(): void
    {
        $this->assertCount(10, $this->requirements());
        $this->assertNotContains(true, $this->requirements());
    }

    public function test_target_market_and_positioning(): void
    {
        Livewire::test(BrandSetup::class)
            ->set('target.target_age', '20–30')
            ->set('target.persona', 'Pasangan muda di Jakarta')
            ->call('saveTarget')
            ->assertHasNoErrors()
            ->set('positioning.price_single_min', '199.000')
            ->set('positioning.price_single_max', '349.000')
            ->call('savePositioning')
            ->assertHasNoErrors()
            ->assertDispatched('step-updated');

        $profile = $this->business->fresh()->brandProfile;
        $this->assertSame('Pasangan muda di Jakarta', $profile->persona);
        $this->assertSame('199000.00', $profile->price_single_min);
        $this->assertTrue($this->requirements()[__('steps.requirements.target_market')]);
        $this->assertTrue($this->requirements()[__('steps.requirements.price_range')]);
    }

    public function test_competitors_need_three(): void
    {
        $component = Livewire::test(BrandSetup::class);
        foreach (['A', 'B'] as $i => $name) {
            $component->call('addCompetitor')->set("competitors.{$i}.name", $name);
        }
        $component->call('saveCompetitors')->assertHasNoErrors();

        $label = __('steps.requirements.competitors', ['count' => 3]);
        $this->assertFalse($this->requirements()[$label]);

        $component->call('addCompetitor')->set('competitors.2.name', 'C')->set('competitors.2.price_min', '150.000')->call('saveCompetitors');
        $this->assertTrue($this->requirements()[$label]);
        $this->assertSame('150000.00', $this->business->competitors()->where('name', 'C')->value('price_min'));

        // Removing a row deletes it.
        Livewire::test(BrandSetup::class)->call('removeCompetitor', 0)->call('saveCompetitors');
        $this->assertSame(['B', 'C'], $this->business->competitors()->pluck('name')->all());
    }

    public function test_final_name_becomes_the_brand_name(): void
    {
        Livewire::test(BrandSetup::class)
            ->call('addName')->set('names.0.name', 'Senja')->set('names.0.instagram_free', '0')
            ->call('addName')->set('names.1.name', 'Fiore Couple')->set('names.1.instagram_free', '1')
            ->set('finalName', '1')
            ->call('saveNames')
            ->assertHasNoErrors();

        $this->assertSame('Fiore Couple', $this->business->fresh()->brand_name);
        $this->assertFalse($this->business->nameCandidates()->where('name', 'Senja')->value('instagram_free'));
        $this->assertTrue($this->requirements()[__('steps.requirements.final_name')]);
    }

    public function test_design_uploads_and_delete(): void
    {
        $component = Livewire::test(BrandSetup::class);
        foreach (['logo', 'label', 'hangtag', 'packaging'] as $kind) {
            $component->set("uploads.{$kind}", $this->fakeImage("{$kind}.png"))->assertHasNoErrors();
        }

        $this->assertSame(4, $this->business->brandAssets()->count());
        $this->assertTrue($this->requirements()[__('steps.requirements.logo')]);
        $this->assertTrue($this->requirements()[__('steps.requirements.label_hangtag')]);
        $this->assertTrue($this->requirements()[__('steps.requirements.packaging')]);

        $logo = $this->business->brandAssets()->where('kind', 'logo')->first();
        Storage::disk('local')->assertExists($logo->path);
        $component->call('deleteAsset', $logo->id);
        Storage::disk('local')->assertMissing($logo->path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Livewire::test(BrandSetup::class)
            ->set('uploads.logo', UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'))
            ->assertHasErrors('uploads.logo');

        $this->assertSame(0, $this->business->brandAssets()->count());
    }

    public function test_brand_kit_accounts_and_capital(): void
    {
        Livewire::test(BrandSetup::class)
            ->set('kit.tagline', 'Pakai berdua, nyaman berdua')
            ->call('addColor')->set('colors.0.name', 'Sage')->set('colors.0.hex', '#9caf88')
            ->call('saveKit')
            ->assertHasNoErrors()
            ->set('accounts.instagram', '@fiore.couple')
            ->call('saveAccounts')
            ->set('startingCapital', '25.000.000')
            ->set('targetMargin', '35,5')
            ->call('saveCapital')
            ->assertHasNoErrors();

        $business = $this->business->fresh();
        $this->assertSame([['name' => 'Sage', 'hex' => '#9caf88']], $business->brandProfile->colors);
        $this->assertSame('@fiore.couple', $business->brandProfile->accounts['instagram']);
        $this->assertSame('25000000.00', $business->starting_capital);
        $this->assertSame('35.50', $business->target_margin_percent);
    }

    public function test_bad_colour_is_rejected(): void
    {
        Livewire::test(BrandSetup::class)
            ->call('addColor')->set('colors.0.hex', 'hijau')
            ->call('saveKit')
            ->assertHasErrors('colors.0.hex');
    }

    public function test_lists_of_makers_and_fabrics(): void
    {
        Vendor::factory()->for($this->business)->create(['type' => VendorType::Maker]);
        $this->business->fabrics()->create(['name' => 'Linen']);

        $this->assertTrue($this->requirements()[__('steps.requirements.makers_listed')]);
        $this->assertTrue($this->requirements()[__('steps.requirements.fabrics_listed')]);
    }

    public function test_another_business_rows_cannot_be_touched(): void
    {
        $foreign = Business::factory()->create()->competitors()->create(['name' => 'Asing']);

        Livewire::test(BrandSetup::class)
            ->set('competitors', [['id' => $foreign->id, 'name' => 'Diubah', 'link' => '', 'price_min' => '', 'price_max' => '', 'strengths' => '', 'weaknesses' => '']])
            ->call('saveCompetitors')
            ->assertNotFound();

        $this->assertSame('Asing', $foreign->fresh()->name);
    }
}
