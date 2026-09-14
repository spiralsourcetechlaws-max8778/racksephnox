<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedMachines extends Command
{
    protected $signature   = 'machines:seed';
    protected $description = 'Seed all RX machine framework tables';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => 'MachinesSeeder', '--force' => true]);
        $this->info('✅ Machines framework seeded.');
        return self::SUCCESS;
    }
}
