<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestQueue extends Command
{
    protected $signature = 'app:test-queue';

    protected $description = 'Dispatch a test job to the queue';

    public function handle(): int
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'TestJob', 'data' => ['test' => now()->toIso8601String()]]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $this->info('Test job dispatched to queue!');

        return self::SUCCESS;
    }
}
