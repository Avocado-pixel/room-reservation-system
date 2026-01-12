<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Optimize application for production.
 *
 * Runs all cache optimization commands:
 * - Route caching
 * - Config caching
 * - View caching
 * - Event caching
 *
 * Usage: php artisan app:optimize
 */
class OptimizeApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize
                            {--clear : Clear all caches before optimizing}
                            {--views : Also compile all Blade views}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the application for production (routes, config, views, events)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Optimizing SAW Room Booking for production...');
        $this->newLine();

        if ($this->option('clear')) {
            $this->warn('Clearing existing caches...');
            $this->callSilently('optimize:clear');
            $this->info('✓ Caches cleared');
        }

        // Cache configuration
        $this->info('Caching configuration...');
        Artisan::call('config:cache');
        $this->info('✓ Configuration cached');

        // Cache routes
        $this->info('Caching routes...');
        Artisan::call('route:cache');
        $this->info('✓ Routes cached');

        // Cache events
        $this->info('Caching events...');
        Artisan::call('event:cache');
        $this->info('✓ Events cached');

        // Compile views
        if ($this->option('views')) {
            $this->info('Compiling Blade views...');
            Artisan::call('view:cache');
            $this->info('✓ Views compiled');
        }

        $this->newLine();
        $this->info('✅ Application optimized for production!');
        $this->newLine();

        // Display cache stats
        $this->table(
            ['Cache Type', 'Status'],
            [
                ['Configuration', '✓ Cached'],
                ['Routes', '✓ Cached'],
                ['Events', '✓ Cached'],
                ['Views', $this->option('views') ? '✓ Compiled' : '○ Skipped (use --views)'],
            ]
        );

        $this->newLine();
        $this->comment('💡 Tip: For Redis caching, ensure CACHE_DRIVER=redis in .env');

        return Command::SUCCESS;
    }
}
