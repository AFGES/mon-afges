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
        Schema::table('session_sign_ins', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('unique_seat_session_signin');

            // Add delegate_id column
            $table->foreignId('delegate_id')
                ->after('seat_id')
                ->constrained('delegates')
                ->cascadeOnDelete()
                ->comment('The specific delegate who signed in');

            // Add new unique constraint allowing multiple delegates per seat
            $table->unique(['democratic_time_session_id', 'seat_id', 'delegate_id'], 'unique_seat_delegate_session_signin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_sign_ins', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique('unique_seat_delegate_session_signin');

            // Drop delegate_id column
            $table->dropForeign(['delegate_id']);
            $table->dropColumn('delegate_id');

            // Restore old unique constraint
            $table->unique(['democratic_time_session_id', 'seat_id'], 'unique_seat_session_signin');
        });
    }
};
