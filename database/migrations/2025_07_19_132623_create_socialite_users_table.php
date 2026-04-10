<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('socialite_users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id');
            $table->string('provider');
            $table->string('provider_id');

            $table->timestamps();

            $table->unique([
                'provider',
                'provider_id',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::dropIfExists('socialite_users');

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->change();
        });
    }
};
