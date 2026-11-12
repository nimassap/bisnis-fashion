<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | Everyone the business buys from: garment makers, fabric suppliers,
        | label/hangtag/packaging printers. Price, MOQ and lead time sit on
        | the vendor so offers of one type can be compared side by side.
        */
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('name', 120);
            $table->string('contact_name', 100)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('location', 120)->nullable();
            $table->unsignedInteger('moq')->nullable();
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->string('price_unit', 30)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('status', 20)->default('prospect');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'type']);
        });

        Schema::create('fabrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('composition', 120)->nullable();
            $table->unsignedSmallInteger('gsm')->nullable();
            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->decimal('price_per_meter', 18, 2)->nullable();
            $table->string('colors', 255)->nullable();

            // Results of the owner's own wash and wear tests.
            $table->decimal('shrinkage_percent', 4, 1)->nullable();
            $table->boolean('colorfast')->nullable();
            $table->unsignedTinyInteger('comfort_rating')->nullable();

            $table->string('status', 20)->default('candidate');
            $table->string('swatch_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
        | A model in a collection. A collection is one couple set, so it holds
        | at most one women's and one men's model.
        */
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->string('gender', 10);
            $table->string('name', 120);
            $table->string('status', 20)->default('idea');
            $table->string('colors', 255)->nullable();

            // Tech pack
            $table->foreignId('fabric_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('fabric_consumption_m', 6, 2)->nullable();
            $table->text('materials')->nullable();
            $table->text('construction_notes')->nullable();
            $table->string('photo_path')->nullable();

            // The measurement points the size chart has columns for.
            $table->json('size_points')->nullable();

            // Copy for the product page.
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(['collection_id', 'gender']);
        });

        Schema::create('product_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('label', 10);
            $table->unsignedTinyInteger('position');

            // Centimetres per measurement point, keyed by point name.
            $table->json('measurements')->nullable();

            $table->timestamps();
        });

        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('round');
            $table->string('status', 20)->default('requested');
            $table->date('requested_on');
            $table->date('received_on')->nullable();
            $table->decimal('cost', 18, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
        Schema::dropIfExists('product_sizes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('fabrics');
        Schema::dropIfExists('vendors');
    }
};
