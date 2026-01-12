<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ListBlockedIps extends Command
{
    protected $signature = 'ip:list';

    protected $description = 'List all currently blocked IP addresses';

    public function handle(): int
    {
        // Get blocked IPs from cache table
        $blocked = DB::table('cache')
            ->where('key', 'like', '%ip_blacklist:%')
            ->get();

        if ($blocked->isEmpty()) {
            $this->info('No IP addresses are currently blocked.');
            return self::SUCCESS;
        }

        $this->info("Currently blocked IPs:\n");

        $rows = $blocked->map(function ($row) {
            // Extract IP from key (format: prefix_ip_blacklist:IP)
            preg_match('/ip_blacklist:(.+)$/', $row->key, $matches);
            $ip = $matches[1] ?? $row->key;
            
            $expiresAt = date('Y-m-d H:i:s', $row->expiration);
            $remaining = max(0, $row->expiration - time());
            $remainingMin = ceil($remaining / 60);

            return [
                'IP' => $ip,
                'Expires' => $expiresAt,
                'Remaining' => "{$remainingMin} min",
            ];
        });

        $this->table(['IP', 'Expires', 'Remaining'], $rows);

        return self::SUCCESS;
    }
}
