<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data for steps 1–4 of a collection: the theme and models, the maker, the
 * fabric, and the label/hangtag/packaging suppliers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->string('theme', 160)->nullable()->after('name');
            $table->text('theme_notes')->nullable()->after('theme');
            $table->foreignId('maker_vendor_id')->nullable()->after('theme_notes')->constrained('vendors')->nullOnDelete();
        });

        // Images that belong to a collection rather than to one model.
        Schema::create('collection_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title', 120)->nullable();
            $table->string('path');
            $table->timestamps();

            $table->index(['collection_id', 'kind']);
        });

        Schema::table('products', function (Blueprint $table) {
            // [{name, hex}] — the colours as swatches; `colors` keeps the names.
            $table->json('swatches')->nullable()->after('colors');
            $table->text('fit_notes')->nullable()->after('construction_notes');
        });

        // Offers from makers for this collection; one is chosen.
        Schema::create('maker_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->decimal('sewing_price_women', 18, 2)->nullable();
            $table->decimal('sewing_price_men', 18, 2)->nullable();
            $table->decimal('sample_cost', 18, 2)->nullable();
            $table->unsignedSmallInteger('sample_days')->nullable();
            $table->unsignedSmallInteger('production_days')->nullable();
            $table->unsignedInteger('moq')->nullable();
            $table->decimal('dp_percent', 5, 2)->nullable();
            $table->string('payment_terms', 255)->nullable();
            $table->unsignedInteger('capacity_per_week')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['collection_id', 'vendor_id']);
        });

        Schema::table('fabrics', function (Blueprint $table) {
            // Knits are sold by the kilo, wovens by the metre. The price and a
            // model's consumption are both in this unit.
            $table->string('price_unit', 10)->default('meter')->after('price_per_meter');
            $table->string('min_order', 60)->nullable()->after('price_unit');
            $table->unsignedSmallInteger('delivery_days')->nullable()->after('min_order');
        });

        // Label, hangtag and packaging: offers per kind, one chosen and ordered.
        Schema::create('trim_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 160)->nullable();
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->unsignedInteger('moq')->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_chosen')->default(false);
            $table->unsignedInteger('order_quantity')->nullable();
            $table->date('ordered_on')->nullable();
            $table->date('received_on')->nullable();
            $table->timestamps();

            $table->index(['collection_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trim_quotes');

        Schema::table('fabrics', function (Blueprint $table) {
            $table->dropColumn(['price_unit', 'min_order', 'delivery_days']);
        });

        Schema::dropIfExists('maker_quotes');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['swatches', 'fit_notes']);
        });

        Schema::dropIfExists('collection_media');

        Schema::table('collections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('maker_vendor_id');
            $table->dropColumn(['theme', 'theme_notes']);
        });
    }
};
