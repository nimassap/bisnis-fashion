<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The business every other record belongs to.
     *
     * For now there is one owner and one business. Everything else (products,
     * fabrics, vendors, cash book) hangs off business_id rather than user_id,
     * so adding team members with roles later means adding a pivot table,
     * not migrating every record.
     */
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            // The brand name is decided in the brand phase, so until then the
            // business goes by a working name.
            $table->string('working_name', 100);
            $table->string('brand_name', 100)->nullable();

            $table->decimal('starting_capital', 18, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
