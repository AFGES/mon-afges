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
        Schema::table('proxies', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['delegator_id']);
            $table->dropForeign(['proxy_holder_id']);

            // Drop unique constraint
            $table->dropUnique('unique_delegator_session');
        });

        Schema::table('proxies', function (Blueprint $table) {
            // Rename columns
            $table->renameColumn('delegator_id', 'delegator_seat_id');
            $table->renameColumn('proxy_holder_id', 'proxy_holder_seat_id');
        });

        Schema::table('proxies', function (Blueprint $table) {
            // Add new foreign keys pointing to seats
            $table->foreign('delegator_seat_id')->references('id')->on('seats')->cascadeOnDelete();
            $table->foreign('proxy_holder_seat_id')->references('id')->on('seats')->cascadeOnDelete();

            // Add unique constraint for seat-based proxies
            $table->unique(['delegator_seat_id', 'democratic_time_session_id'], 'unique_delegator_seat_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proxies', function (Blueprint $table) {
            // Drop seat-based foreign keys
            $table->dropForeign(['delegator_seat_id']);
            $table->dropForeign(['proxy_holder_seat_id']);

            // Drop unique constraint
            $table->dropUnique('unique_delegator_seat_session');
        });

        Schema::table('proxies', function (Blueprint $table) {
            // Rename columns back
            $table->renameColumn('delegator_seat_id', 'delegator_id');
            $table->renameColumn('proxy_holder_seat_id', 'proxy_holder_id');
        });

        Schema::table('proxies', function (Blueprint $table) {
            // Restore delegate-based foreign keys
            $table->foreign('delegator_id')->references('id')->on('delegates')->cascadeOnDelete();
            $table->foreign('proxy_holder_id')->references('id')->on('delegates')->cascadeOnDelete();

            // Restore original unique constraint
            $table->unique(['delegator_id', 'democratic_time_session_id'], 'unique_delegator_session');
        });
    }
};
