<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\AuditLog;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('audit:prune {--days=90} {--chunk=500}', function () {
    $days = max((int) $this->option('days'), 1);
    $chunk = max((int) $this->option('chunk'), 100);
    $threshold = now()->subDays($days);

    $deleted = 0;

    do {
        $count = AuditLog::where('created_at', '<', $threshold)
            ->limit($chunk)
            ->delete();
        $deleted += $count;
    } while ($count === $chunk);

    $this->info("Pruned {$deleted} audit log rows older than {$days} days.");
})->purpose('Prune old audit log entries');

Schedule::command('audit:prune --days=90 --chunk=1000')->dailyAt('03:00');
