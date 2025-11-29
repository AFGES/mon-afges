<?php

use App\Enums\College;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            $table->enum('college', College::cases())->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            $table->dropColumn('college');
        });
    }
};
