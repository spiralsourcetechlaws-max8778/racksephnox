<?php

namespace Database\Seeders;

use App\Models\LotteryMission;
use Illuminate\Database\Seeder;

class LotteryMissionSeeder extends Seeder
{
    public function run(): void
    {
        $missions = [
            ['name' => 'Spin 10 Times',      'description' => 'Complete 10 spins today',     'requirement_type' => 'spins',      'requirement_value' => 10,    'reward_amount' => 50,   'is_active' => true],
            ['name' => 'Win 5 Spins',        'description' => 'Win at least 5 spins today',   'requirement_type' => 'wins',       'requirement_value' => 5,     'reward_amount' => 100,  'is_active' => true],
            ['name' => 'Big Win Hunter',     'description' => 'Hit a 1,000+ win',             'requirement_type' => 'big_win',    'requirement_value' => 1,     'reward_amount' => 200,  'is_active' => true],
            ['name' => 'Bet 500 Total',      'description' => 'Wager 500 KES today',          'requirement_type' => 'total_bet',  'requirement_value' => 500,   'reward_amount' => 75,   'is_active' => true],
            ['name' => 'Jackpot Seeker',     'description' => 'Win a jackpot today',          'requirement_type' => 'jackpot_win','requirement_value' => 1,     'reward_amount' => 500,  'is_active' => true],
        ];

        foreach ($missions as $m) {
            LotteryMission::updateOrCreate(['name' => $m['name']], $m);
        }

        $this->command->info('✅ Lottery missions: ' . LotteryMission::count());
    }
}
