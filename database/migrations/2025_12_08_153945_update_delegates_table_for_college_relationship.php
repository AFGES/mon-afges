<?php

use App\Models\College;
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
        // First, seed the colleges table if it's empty
        if (College::count() === 0) {
            College::create(['name' => 'Général', 'can_vote' => true]);
            College::create(['name' => 'Fédéral', 'can_vote' => true]);
            College::create(['name' => 'Consultatif', 'can_vote' => false]);
        }

        Schema::table('delegates', function (Blueprint $table) {
            // Add the new college_id column
            $table->foreignId('college_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            // Drop the foreign key column
            $table->dropForeign(['college_id']);
            $table->dropColumn('college_id');
        });
    }
};
