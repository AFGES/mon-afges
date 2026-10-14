<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const string FULL_NAME_EXPRESSION = "trim(coalesce(first_name, '') || ' ' || coalesce(last_name, ''))";

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
        });

        DB::table('users')->select(['id', 'name'])->lazyById()->each(function (object $user): void {
            $name = Str::squish((string) $user->name);

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => Str::before($name, ' ') ?: null,
                'last_name' => Str::contains($name, ' ') ? Str::after($name, ' ') : null,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $column = $table->string('name');

            DB::getDriverName() === 'sqlite'
                ? $column->virtualAs(self::FULL_NAME_EXPRESSION)
                : $column->storedAs(self::FULL_NAME_EXPRESSION);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->default('');
        });

        DB::table('users')->update(['name' => DB::raw(self::FULL_NAME_EXPRESSION)]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
