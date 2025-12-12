<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


// Schedule daily sync of delegates to Google Workspace
Schedule::command('delegates:sync-google-workspace')
    ->daily()
    ->timezone('Europe/Paris');
