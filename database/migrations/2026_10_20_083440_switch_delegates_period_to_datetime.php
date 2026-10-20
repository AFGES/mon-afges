<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Periods are now half-open datetimes [started_at, ended_at). Delegate end dates used to be inclusive
     * days, so they move to the start of the following day to keep the same last active day. Volunteer ends
     * stored at exactly midnight were inclusive days too and shift the same way. Sessions are left untouched.
     */
    public function up(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            $table->dateTime('started_at')->useCurrent()->change();
            $table->dateTime('ended_at')->nullable()->change();
        });

        $this->shiftEnds('delegates', 1, onlyAtMidnight: false);
        $this->shiftEnds('volunteers', 1, onlyAtMidnight: true);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->shiftEnds('delegates', -1, onlyAtMidnight: false);
        $this->shiftEnds('volunteers', -1, onlyAtMidnight: true);

        Schema::table('delegates', function (Blueprint $table) {
            $table->date('started_at')->useCurrent()->change();
            $table->date('ended_at')->nullable()->change();
        });
    }

    private function shiftEnds(string $table, int $days, bool $onlyAtMidnight): void
    {
        DB::table($table)
            ->whereNotNull('ended_at')
            ->chunkById(200, function ($rows) use ($table, $days, $onlyAtMidnight): void {
                foreach ($rows as $row) {
                    $endedAt = Carbon::parse($row->ended_at);

                    if ($onlyAtMidnight && ! $endedAt->equalTo($endedAt->copy()->startOfDay())) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['ended_at' => $endedAt->addDays($days)->toDateTimeString()]);
                }
            }, 'id');
    }
};
