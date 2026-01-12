<?php

use App\Jobs\ManageSessionSignIns;
use Illuminate\Support\Facades\Schedule;

// Schedule daily sync of delegates to Google Workspace
Schedule::command('delegates:sync-google-workspace')
    ->daily()
    ->timezone('Europe/Paris');

// Schedule sign-in management every 5 minutes
Schedule::job(new ManageSessionSignIns)
    ->everyFiveMinutes()
    ->timezone('Europe/Paris');
