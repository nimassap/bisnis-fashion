<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fabric widths are not always whole centimetres: 36,5 cm, or a width
 * converted from inches. Existing whole numbers carry over unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fabrics', function (Blueprint $table) {
            $table->decimal('width_cm', 6, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fabrics', function (Blueprint $table) {
            $table->unsignedSmallInteger('width_cm')->nullable()->change();
        });
    }
};
