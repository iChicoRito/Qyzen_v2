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
// every minute. On Hostinger shared hosting, use a Custom cron scheduled every minute; hPanel
// owns the schedule/output settings, so the command field contains no cron prefix or redirection:
//   /usr/bin/php /home/u560807207/domains/qyzen.space/public_html/artisan schedule:run
Schedule::command('backup:database')->daily()->withoutOverlapping();
Schedule::command('notifications:prune')->daily()->withoutOverlapping();
