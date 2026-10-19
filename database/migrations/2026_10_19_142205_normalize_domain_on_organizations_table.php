<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Domains are now matched exactly, so existing ones are stored lowercase without a leading at sign.
     */
    public function up(): void
    {
        DB::table('organizations')
            ->whereNotNull('domain')
            ->get(['id', 'domain'])
            ->each(function (object $organization): void {
                $domain = Str::of($organization->domain)->trim()->ltrim('@')->lower()->toString();

                DB::table('organizations')
                    ->where('id', $organization->id)
                    ->update(['domain' => $domain === '' ? null : $domain]);
            });

        Schema::table('organizations', function (Blueprint $table) {
            $table->index('domain');
        });
    }

    /**
     * Reverse the migrations.
     *
     * The original formatting of the domains is not kept, so only the index is dropped.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['domain']);
        });
    }
};
