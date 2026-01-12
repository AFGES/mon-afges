<?php

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
            $table->id();
            $table->foreignId('delegator_id')->constrained('delegates')->cascadeOnDelete();
            $table->foreignId('proxy_holder_id')->constrained('delegates')->cascadeOnDelete();
            $table->foreignId('democratic_time_session_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            // Prevent overlapping proxies for the same delegator in the same session
            $table->unique(['delegator_id', 'democratic_time_session_id'], 'unique_delegator_session');
        });

        // Add check constraint to ensure a delegate can't give proxy to themselves
        // Different syntax for different database drivers
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER TABLE ADD CONSTRAINT for CHECK constraints
            // We need to recreate the table or use a trigger as workaround
            // For now, we'll rely on application-level validation
        } else {
            // MySQL/PostgreSQL syntax
            DB::statement('ALTER TABLE proxies ADD CONSTRAINT check_different_delegates CHECK (delegator_id != proxy_holder_id)');
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
