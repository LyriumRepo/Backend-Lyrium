<?php

namespace App\Console\Commands;

use App\Models\Banner;
use Illuminate\Console\Command;

class ClearBanners extends Command
{
    protected $signature = 'app:clear-banners';

    protected $description = 'Clear all banners';

    public function handle(): int
    {
        Banner::truncate();
        $this->info('Banners cleared.');

        return self::SUCCESS;
    }
}
