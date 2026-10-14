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
        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('postal_address')->nullable();
            $table->string('study_program')->nullable();
            $table->text('elected_mandates')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'phone', 'postal_address', 'study_program', 'elected_mandates']);
        });
    }
};
