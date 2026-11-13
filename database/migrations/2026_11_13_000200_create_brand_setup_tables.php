<?php

use App\Actions\Phases\SeedPhases;
use App\Models\Business;
use App\Models\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Setup Brand as data rather than a checklist, and the move from six
 * checklist phases to Setup + nine steps per collection.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One row per business: who the brand is for and how it presents.
        Schema::create('brand_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();

            // Target market
            $table->string('target_age', 40)->nullable();
            $table->string('target_gender', 60)->nullable();
            $table->string('target_cities', 160)->nullable();
            $table->string('target_style', 160)->nullable();
            $table->string('target_budget', 120)->nullable();
            $table->text('persona')->nullable();

            // Positioning
            $table->text('positioning')->nullable();
            $table->decimal('price_single_min', 18, 2)->nullable();
            $table->decimal('price_single_max', 18, 2)->nullable();
            $table->decimal('price_bundle_min', 18, 2)->nullable();
            $table->decimal('price_bundle_max', 18, 2)->nullable();

            // Brand kit
            $table->string('tagline', 160)->nullable();
            $table->text('tone')->nullable();
            $table->json('colors')->nullable();
            $table->string('fonts', 160)->nullable();

            // Social and store accounts, keyed by platform.
            $table->json('accounts')->nullable();

            $table->timestamps();
        });

        Schema::create('competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('link', 255)->nullable();
            $table->decimal('price_min', 18, 2)->nullable();
            $table->decimal('price_max', 18, 2)->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_name_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);

            // null = not checked yet, true = free, false = taken.
            $table->boolean('instagram_free')->nullable();
            $table->boolean('tiktok_free')->nullable();
            $table->boolean('domain_free')->nullable();
            $table->boolean('trademark_free')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        // Design files: logo, label, hangtag and packaging, several versions each.
        Schema::create('brand_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title', 120)->nullable();
            $table->string('path');
            $table->timestamps();

            $table->index(['business_id', 'kind']);
        });

        // The six checklist phases make way for Setup + nine steps. The owner
        // agreed to start the step checklists over; products, fabrics,
        // vendors, prices, cash and sales are untouched.
        DB::table('decisions')->delete();
        DB::table('phase_tasks')->delete();
        DB::table('phases')->delete();

        $seed = app(SeedPhases::class);
        Business::query()->each(fn (Business $business) => $seed->forBusiness($business));
        Collection::query()->with('business')->each(fn (Collection $collection) => $seed->forCollection($collection));
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_assets');
        Schema::dropIfExists('brand_name_candidates');
        Schema::dropIfExists('competitors');
        Schema::dropIfExists('brand_profiles');
    }
};
