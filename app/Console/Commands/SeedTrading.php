<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedTrading extends Command
{
    protected $signature   = 'trading:seed';
    protected $description = 'Seed all trading framework tables';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => 'TradingSeeder', '--force' => true]);
        $this->info('✅ Trading framework seeded.');
        return self::SUCCESS;
    }
}
