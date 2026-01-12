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
        Schema::create('session_sign_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('democratic_time_session_id')
                ->constrained('democratic_time_sessions')
                ->cascadeOnDelete();
            $table->foreignUuid('seat_id')
                ->constrained('seats')
                ->cascadeOnDelete();
            $table->foreignUuid('delegate_id')
                ->constrained('delegates')
                ->cascadeOnDelete()
                ->comment('The specific delegate who signed in');
            $table->foreignUuid('signed_in_by_seat_id')
                ->nullable()
                ->constrained('seats')
                ->nullOnDelete()
                ->comment('Seat that signed in on behalf (via proxy)');
            $table->timestamp('signed_in_at');
            $table->timestamp('signed_out_at')->nullable();
            $table->timestamps();

            // Unique constraint: one sign-in record per delegate per seat per session
            $table->unique(['democratic_time_session_id', 'seat_id', 'delegate_id'], 'unique_seat_delegate_session_signin');

            // Index for performance
            $table->index(['democratic_time_session_id', 'signed_in_at']);
        });

        // Add sign-in management fields to democratic_time_sessions table
        Schema::table('democratic_time_sessions', function (Blueprint $table) {
            $table->timestamp('sign_in_opens_at')->nullable()->after('location');
            $table->timestamp('sign_in_closes_at')->nullable()->after('sign_in_opens_at');
            $table->boolean('auto_open_sign_in')->default(true)->after('sign_in_closes_at');
            $table->integer('minutes_before_start')->default(15)->after('auto_open_sign_in')
                ->comment('Minutes before started_at to auto-open sign-in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('democratic_time_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'sign_in_opens_at',
                'sign_in_closes_at',
                'auto_open_sign_in',
                'minutes_before_start',
            ]);
        });

        Schema::dropIfExists('session_sign_ins');
    }
};
