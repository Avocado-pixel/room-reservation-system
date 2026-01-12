<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\CodigoValidacaoConta;
use App\Support\SawKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ResendPendingValidationCodes extends Command
{
    protected $signature = 'saw:resend-pending-codes
        {--limit=50 : Max emails to send in this run}
        {--only-email= : Only send to this email (useful for testing)}
        {--dry-run : Do not write/send, only show what would happen}';

    protected $description = 'Generates (HMAC) and resends validation codes for pending accounts without a token (legacy SAW flow).';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        if ($limit <= 0) {
            $limit = 50;
        }

        $onlyEmail = $this->option('only-email');
        $dryRun = (bool) $this->option('dry-run');

        $query = User::query()
            ->where('estado', 'pending')
            ->where(function ($q) {
                $q->whereNull('token_validacao_email')
                    ->orWhereNull('token_validacao_email_expira')
                    ->orWhere('token_validacao_email_expira', '<', now());
            })
            ->orderBy('id');

        if (is_string($onlyEmail) && $onlyEmail !== '') {
            $query->where('email', $onlyEmail);
        }

        $users = $query->limit($limit)->get();

        if ($users->isEmpty()) {
            $this->info('No pending users to process.');
            return self::SUCCESS;
        }

        $this->info('Found '.$users->count().' pending users to resend codes.');

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $codigo = (string) random_int(100000, 999999);
            $token = hash_hmac('sha256', $codigo, SawKeys::hmacKey());

            $this->line(' - '.$user->email.' (id='.$user->id.')');

            if ($dryRun) {
                continue;
            }

            $user->forceFill([
                'token_validacao_email' => $token,
                'token_validacao_email_expira' => now()->addMinutes(30),
            ])->save();

            try {
                Notification::route('mail', $user->email)
                    ->notify(new CodigoValidacaoConta($user->name, $codigo));
                $sent++;
            } catch (Throwable $e) {
                $failed++;
                $this->error('   Failed to send to '.$user->email.': '.$e->getMessage());
            }
        }

        if ($dryRun) {
            $this->warn('Dry-run: nothing was saved/sent.');
            return self::SUCCESS;
        }

        $this->info('Sent: '.$sent.' | Failed: '.$failed);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
