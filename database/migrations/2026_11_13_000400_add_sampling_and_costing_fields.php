<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 5 (sampling review) and step 6 (costing and pricing).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            // The size the sample was sewn in, and what it actually measured,
            // keyed by the size chart's measurement points.
            $table->string('size', 10)->nullable()->after('status');
            $table->json('measurements')->nullable()->after('size');

            // Verdict per review point: fit, stitching, colour, comfort.
            $table->json('review')->nullable()->after('measurements');

            $table->string('photo_path')->nullable()->after('review');

            // The approved sample production follows.
            $table->boolean('is_golden')->default(false)->after('photo_path');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('strike_price', 18, 2)->nullable()->after('price');
            $table->decimal('reseller_price', 18, 2)->nullable()->after('strike_price');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->decimal('bundle_strike_price', 18, 2)->nullable()->after('bundle_price');

            // Launch voucher and free-shipping subsidy, per piece sold.
            $table->decimal('promo_cost_per_piece', 18, 2)->nullable()->after('bundle_strike_price');

            // Money set aside for this collection's production.
            $table->decimal('available_capital', 18, 2)->nullable()->after('promo_cost_per_piece');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['bundle_strike_price', 'promo_cost_per_piece', 'available_capital']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['strike_price', 'reseller_price']);
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->dropColumn(['size', 'measurements', 'review', 'photo_path', 'is_golden']);
        });
    }
};
