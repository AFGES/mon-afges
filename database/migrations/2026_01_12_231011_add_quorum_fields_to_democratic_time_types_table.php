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
        Schema::table('democratic_time_types', function (Blueprint $table) {
            $table->boolean('calculate_quorum_per_college')
                ->default(false)
                ->after('convocation_interval')
                ->comment('Whether to calculate quorum per college (true) or all colleges combined (false)');

            $table->decimal('quorum_percentage', 5, 2)
                ->default(50.00)
                ->after('calculate_quorum_per_college')
                ->comment('Percentage of delegates required for quorum');

            $table->boolean('include_proxies_in_quorum')
                ->default(true)
                ->after('quorum_percentage')
                ->comment('Whether to count proxies when calculating quorum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('democratic_time_types', function (Blueprint $table) {
            $table->dropColumn([
                'calculate_quorum_per_college',
                'quorum_percentage',
                'include_proxies_in_quorum',
            ]);
        });
    }
};
