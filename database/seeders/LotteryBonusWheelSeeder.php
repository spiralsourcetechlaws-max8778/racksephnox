<?php

namespace Database\Seeders;

use App\Models\LotteryBonusWheel;
use App\Services\Lottery\BonusWheelService;
use Illuminate\Database\Seeder;

class LotteryBonusWheelSeeder extends Seeder
{
    public function run(): void
    {
        LotteryBonusWheel::updateOrCreate(
            ['name' => 'Cosmic Fortune Wheel'],
            [
                'segments'  => BonusWheelService::defaultSegments(),
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Lottery bonus wheel seeded.');
    }
}
