<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            // Remove the direct college_id relationship
            $table->dropForeign(['college_id']);
            $table->dropColumn('college_id');

            // Add seat_id foreign key
            $table->foreignId('seat_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            // Remove seat_id
            $table->dropForeign(['seat_id']);
            $table->dropColumn('seat_id');

            // Restore college_id
            $table->foreignId('college_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }
};
