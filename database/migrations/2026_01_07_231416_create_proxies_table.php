<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proxies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('delegator_seat_id')->constrained('seats')->cascadeOnDelete();
            $table->foreignUuid('proxy_holder_seat_id')->constrained('seats')->cascadeOnDelete();
            $table->foreignUuid('democratic_time_session_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->approvalStatus('ended_at');
            $table->timestamps();

            // Prevent overlapping proxies for the same delegator in the same session
            $table->unique(['delegator_seat_id', 'democratic_time_session_id'], 'unique_delegator_seat_session');
        });

        // Add check constraint to ensure a seat can't give proxy to itself
        // Different syntax for different database drivers
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER TABLE ADD CONSTRAINT for CHECK constraints
            // We need to recreate the table or use a trigger as workaround
            // For now, we'll rely on application-level validation
        } else {
            // MySQL/PostgreSQL syntax
            DB::statement('ALTER TABLE proxies ADD CONSTRAINT check_different_seats CHECK (delegator_seat_id != proxy_holder_seat_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxies');
    }
};
