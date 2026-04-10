<?php

declare(strict_types=1);

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
        Schema::table('democratic_time_sessions', function (Blueprint $table) {
            $table->boolean('calculate_quorum_per_college')
                ->nullable()
                ->after('minutes_before_start')
                ->comment('Snapshot: Whether to calculate quorum per college (true) or all colleges combined (false)');

            $table->decimal('quorum_percentage', 5, 2)
                ->nullable()
                ->after('calculate_quorum_per_college')
                ->comment('Snapshot: Percentage of delegates required for quorum');

            $table->boolean('include_proxies_in_quorum')
                ->nullable()
                ->after('quorum_percentage')
                ->comment('Snapshot: Whether to count proxies when calculating quorum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('democratic_time_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'calculate_quorum_per_college',
                'quorum_percentage',
                'include_proxies_in_quorum',
            ]);
        });
    }
};
