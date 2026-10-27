<?php

namespace App\Console\Commands;

use App\Services\MachineService;
use Illuminate\Console\Command;

class AccrueMachineProfits extends Command
{
    protected $signature = 'machine:accrue';
    protected $description = 'Credit daily profit to all active machine investments';

    public function handle(MachineService $service): int
    {
        $this->info('Starting daily profit accrual...');

        try {
            $count = $service->accrueDailyProfits();
            $this->info("✅ Credited {$count} investment(s)");
            \Log::info('machine:accrue completed', ['credited' => $count]);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Failed: ' . $e->getMessage());
            \Log::error('machine:accrue failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return self::FAILURE;
        }
    }
}
