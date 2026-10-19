<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The legacy free-text addresses are discarded: members pick a structured address again.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('address_street')->nullable();
            $table->string('address_postcode', 20)->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_country', 2)->nullable();
            $table->decimal('address_latitude', 10, 7)->nullable();
            $table->decimal('address_longitude', 10, 7)->nullable();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('postal_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('postal_address')->nullable();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'address_street',
                'address_postcode',
                'address_city',
                'address_country',
                'address_latitude',
                'address_longitude',
            ]);
        });
    }
};
