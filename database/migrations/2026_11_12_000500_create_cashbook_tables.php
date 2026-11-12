<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 3);
            $table->string('name', 60);
            $table->string('icon', 30)->default('list');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        /*
        | Money paid by the business to vendors on a schedule: a deposit to the
        | maker, the balance on delivery. Marking one paid writes the matching
        | cash book entry.
        */
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cash_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 120);
            $table->decimal('amount', 18, 2);
            $table->date('due_on');
            $table->date('paid_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'paid_on', 'due_on']);
        });

        /*
        | Every rupiah in and out. Entries outlive what they mention: deleting
        | a collection, vendor or category leaves the money where it was.
        */
        Schema::create('cash_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 3);
            $table->decimal('amount', 18, 2);
            $table->date('occurred_on');
            $table->string('description', 160);
            $table->foreignId('cash_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'occurred_on']);
        });

        // How much a collection may spend per category.
        Schema::create('collection_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_category_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->timestamps();

            $table->unique(['collection_id', 'cash_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_budgets');
        Schema::dropIfExists('cash_entries');
        Schema::dropIfExists('vendor_payments');
        Schema::dropIfExists('cash_categories');
    }
};
