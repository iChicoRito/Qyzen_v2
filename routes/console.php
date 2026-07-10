<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Task 05 (task 2): daily database backup, retained 7-deep (see BackupDatabase::pruneOldBackups).
// Laravel 13 has no app/Console/Kernel.php — schedules are registered here.
//
// This only *decides* when to run; something outside Laravel still has to invoke the scheduler
// every minute. On Hostinger shared hosting, add this to hPanel -> Advanced -> Cron Jobs, set to
// run every minute (Laravel itself decides daily-vs-not from ->daily() below):
//   php /home/<user>/domains/<domain>/artisan schedule:run >> /dev/null 2>&1
Schedule::command('backup:database')->daily()->withoutOverlapping();
