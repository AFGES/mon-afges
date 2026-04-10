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
        Schema::create('democratic_time_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('democratic_time_type_id')->constrained()->cascadeOnDelete();
            $table->period();
            $table->string('location');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('democratic_time_sessions');
    }
};
