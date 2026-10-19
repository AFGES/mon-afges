<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Emails are now matched exactly against their lowercase form. Existing users
     * predate email verification on the panel, so they are not locked behind it.
     * Fails on the unique index if two emails only differ by case.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereRaw('email <> LOWER(TRIM(email))')
            ->update(['email' => DB::raw('LOWER(TRIM(email))')]);

        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Reverse the migrations.
     *
     * The original casing and verification state are not kept, so nothing is restored.
     */
    public function down(): void {}
};
