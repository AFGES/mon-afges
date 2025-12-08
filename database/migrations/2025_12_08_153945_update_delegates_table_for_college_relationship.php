<?php

use App\Enums\College as CollegeEnum;
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
            College::create(['name' => 'General', 'can_vote' => true]);
            College::create(['name' => 'Federal', 'can_vote' => true]);
            College::create(['name' => 'Advisory', 'can_vote' => false]);
        }

        Schema::table('delegates', function (Blueprint $table) {
            // Add the new college_id column
            $table->foreignId('college_id')->nullable()->constrained()->cascadeOnDelete();
        });

        // Migrate existing data from enum to foreign key
        $generalCollege = College::where('name', 'General')->first();
        $federalCollege = College::where('name', 'Federal')->first();

        DB::table('delegates')
            ->where('college', 'General')
            ->update(['college_id' => $generalCollege->id]);

        DB::table('delegates')
            ->where('college', 'Federal')
            ->update(['college_id' => $federalCollege->id]);

        Schema::table('delegates', function (Blueprint $table) {
            // Remove the old enum column
            $table->dropColumn('college');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            // Re-add the enum column
            $table->enum('college', ['General', 'Federal'])->nullable();
        });

        // Migrate data back from foreign key to enum
        $generalCollege = College::where('name', 'General')->first();
        $federalCollege = College::where('name', 'Federal')->first();

        if ($generalCollege) {
            DB::table('delegates')
                ->where('college_id', $generalCollege->id)
                ->update(['college' => 'General']);
        }

        if ($federalCollege) {
            DB::table('delegates')
                ->where('college_id', $federalCollege->id)
                ->update(['college' => 'Federal']);
        }

        Schema::table('delegates', function (Blueprint $table) {
            // Drop the foreign key column
            $table->dropForeign(['college_id']);
            $table->dropColumn('college_id');
        });
    }
};
