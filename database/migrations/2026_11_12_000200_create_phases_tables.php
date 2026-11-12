<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | A collection is one production run: one couple set, a women's and
        | a men's model made and launched together. The brand phase happens
        | once per business; every other phase repeats per collection.
        */
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // Shown as "Koleksi #3": one higher than the highest so far.
            $table->unsignedInteger('number');
            $table->string('name', 100)->nullable();
            $table->date('target_launch_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'number']);
        });

        /*
        | A phase belongs to the business (brand scope, collection_id null)
        | or to one collection. business_id is set on both, so every lookup
        | can be scoped to the owner's business in one condition. The key
        | ties a row to its definition in config/phases.php; position is
        | copied in so ordering never depends on config staying unchanged.
        */
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key', 30);
            $table->unsignedTinyInteger('position');
            $table->date('target_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'collection_id', 'key']);
        });

        Schema::create('phase_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('notes')->nullable();
            $table->date('due_on')->nullable();

            // A gate task must be done before its phase can be closed.
            $table->boolean('is_gate')->default(false);

            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['phase_id', 'position']);
        });

        /*
        | Why a choice was made, kept next to the phase it was made in:
        | "linen from supplier B, A shrank 6% in the wash test".
        */
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phase_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            $table->text('reason')->nullable();
            $table->date('decided_on');
            $table->timestamps();

            $table->index(['business_id', 'decided_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
        Schema::dropIfExists('phase_tasks');
        Schema::dropIfExists('phases');
        Schema::dropIfExists('collections');
    }
};
