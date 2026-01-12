<?php

namespace App\Console\Commands;

use App\Services\Security\IpBlacklistService;
use Illuminate\Console\Command;

class UnblockIp extends Command
{
    protected $signature = 'ip:unblock {ip : The IP address to unblock}';

    protected $description = 'Remove an IP address from the blacklist';

    public function handle(IpBlacklistService $blacklistService): int
    {
        $ip = $this->argument('ip');

        if (!$blacklistService->isBlacklisted($ip)) {
            $this->info("IP {$ip} is not currently blocked.");
            return self::SUCCESS;
        }

        $blacklistService->unblacklist($ip);
        $this->info("✓ IP {$ip} has been unblocked successfully.");

        return self::SUCCESS;
    }
}
