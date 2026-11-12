<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Profit as a share of the selling price, after channel fees.
            $table->decimal('target_margin_percent', 5, 2)->default(40)->after('starting_capital');
        });

        Schema::table('collections', function (Blueprint $table) {
            // The couple set sold together, women's + men's in one order.
            $table->decimal('bundle_price', 18, 2)->nullable()->after('target_launch_on');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('fabric_waste_percent', 5, 2)->default(5)->after('fabric_consumption_m');
            $table->decimal('sewing_cost', 18, 2)->nullable()->after('fabric_waste_percent');
            $table->unsignedInteger('planned_quantity')->nullable()->after('sewing_cost');
            $table->decimal('price', 18, 2)->nullable()->after('planned_quantity');
        });

        // Per-piece costs beyond fabric and sewing: label, hangtag, packaging…
        Schema::create('product_cost_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->decimal('amount', 18, 2)->default(0);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        // One-off costs of a collection, spread over every piece produced:
        // photoshoot, launch ads, shipping of materials…
        Schema::create('collection_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->decimal('amount', 18, 2)->default(0);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('sales_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->decimal('fixed_fee', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_channels');
        Schema::dropIfExists('collection_costs');
        Schema::dropIfExists('product_cost_items');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['fabric_waste_percent', 'sewing_cost', 'planned_quantity', 'price']);
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('bundle_price');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('target_margin_percent');
        });
    }
};
