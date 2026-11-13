<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Steps 7–9: the purchase order per size and colour, the production
 * stages, QC, the marketplace listings, and the launch and its evaluation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedInteger('planned_quantity')->default(0)->after('color');
            $table->unsignedInteger('rejected_quantity')->default(0)->after('produced_quantity');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->date('production_started_on')->nullable();
            $table->date('production_due_on')->nullable();
            $table->date('materials_sent_on')->nullable();
            $table->date('cutting_done_on')->nullable();
            $table->date('sewing_done_on')->nullable();
            $table->date('finishing_done_on')->nullable();
            $table->date('qc_done_on')->nullable();
            $table->date('received_on')->nullable();
            $table->string('shipping_courier', 60)->nullable();
            $table->string('shipping_tracking', 80)->nullable();
            $table->text('shot_list')->nullable();
            $table->text('launch_concept')->nullable();
            $table->string('launch_promo', 160)->nullable();
            $table->date('launched_on')->nullable();
            $table->text('evaluation')->nullable();
        });

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_channel_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160)->nullable();
            $table->text('description')->nullable();
            $table->string('url', 255)->nullable();
            $table->string('sku', 60)->nullable();
            $table->boolean('is_live')->default(false);
            $table->timestamps();

            $table->unique(['collection_id', 'sales_channel_id']);
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            // Marks the down payment and the settlement made from step 7, so
            // making them again updates instead of doubling.
            $table->string('source', 30)->nullable()->after('cash_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_payments', fn (Blueprint $table) => $table->dropColumn('source'));
        Schema::dropIfExists('listings');
        Schema::table('collections', fn (Blueprint $table) => $table->dropColumn([
            'production_started_on', 'production_due_on', 'materials_sent_on', 'cutting_done_on', 'sewing_done_on',
            'finishing_done_on', 'qc_done_on', 'received_on', 'shipping_courier', 'shipping_tracking', 'shot_list',
            'launch_concept', 'launch_promo', 'launched_on', 'evaluation',
        ]));
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn(['planned_quantity', 'rejected_quantity']));
    }
};
