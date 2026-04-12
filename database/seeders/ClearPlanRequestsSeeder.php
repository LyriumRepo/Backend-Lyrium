<?php

namespace Database\Seeders;

use App\Models\PlanRequest;
use Illuminate\Database\Seeder;

class ClearPlanRequestsSeeder extends Seeder
{
    public function run(): void
    {
        PlanRequest::truncate();
        $this->command->info('All plan requests have been deleted.');
    }
}
