<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ServiceSlotHold;
use Illuminate\Console\Command;

final class ReleaseExpiredSlotHolds extends Command
{
    protected $signature = 'services:release-expired-holds';

    protected $description = 'Release expired slot holds so other users can book';

    public function handle(): int
    {
        $deleted = ServiceSlotHold::where('expires_at', '<=', now())->delete();

        $this->info("Released {$deleted} expired slot hold(s).");

        return self::SUCCESS;
    }
}
