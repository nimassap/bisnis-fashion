<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        | One sellable version of a model: a size in a colour. Produced is
        | what came back from the maker; what is left is produced minus what
        | completed orders took.
        */
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size', 10);
            $table->string('color', 40)->default('');
            $table->unsignedInteger('produced_quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'size', 'color']);
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('type', 20);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->decimal('budget', 18, 2)->nullable();
            $table->decimal('spend', 18, 2)->default(0);
            $table->string('kol_handle', 100)->nullable();
            $table->string('voucher_code', 40)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->date('ordered_on');
            $table->string('reference', 60)->nullable();
            $table->string('customer_name', 100)->nullable();
            $table->string('status', 20)->default('completed');
            $table->decimal('discount', 18, 2)->default(0);

            // What the channel kept, worked out when the order was recorded
            // so a later change to the channel's fee leaves history alone.
            $table->decimal('fee_amount', 18, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'ordered_on']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            // Kept as written, so an order still reads right after the model
            // is renamed or deleted.
            $table->string('product_name', 120);
            $table->string('size', 10);
            $table->string('color', 40)->default('');

            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 18, 2);

            // HPP per piece when sold, for gross profit.
            $table->decimal('unit_cost', 18, 2)->nullable();

            // The two halves of a couple bundle share a group number within
            // the order; the bundle price is split between them.
            $table->unsignedSmallInteger('bundle_group')->nullable();

            $table->timestamps();
        });

        Schema::table('cash_entries', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->unique()->after('vendor_payment_id')->constrained()->nullOnDelete();
        });

        Schema::create('content_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform', 20);
            $table->string('format', 20);
            $table->date('scheduled_on');
            $table->string('title', 160);
            $table->string('status', 20)->default('idea');
            $table->string('url', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'scheduled_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_posts');

        Schema::table('cash_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('product_variants');
    }
};
