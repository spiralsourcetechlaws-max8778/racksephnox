<?php

namespace Database\Seeders;

use App\Models\LotteryAchievement;
use Illuminate\Database\Seeder;

class LotteryAchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            ['name' => 'First Spin',        'description' => 'Take your first spin',          'requirement_type' => 'total_spins',  'requirement_value' => 1,      'reward_amount' => 10,    'icon' => '▶️'],
            ['name' => 'Getting Started',   'description' => '10 spins',                      'requirement_type' => 'total_spins',  'requirement_value' => 10,     'reward_amount' => 50,    'icon' => '🚩'],
            ['name' => 'Centurion',         'description' => '100 spins',                     'requirement_type' => 'total_spins',  'requirement_value' => 100,    'reward_amount' => 500,   'icon' => '🛡️'],
            ['name' => 'Thousand Club',     'description' => '1,000 spins',                   'requirement_type' => 'total_spins',  'requirement_value' => 1000,   'reward_amount' => 5000,  'icon' => '👑'],
            ['name' => 'First Win',         'description' => 'Win your first spin',           'requirement_type' => 'total_wins',   'requirement_value' => 1,      'reward_amount' => 20,    'icon' => '🏆'],
            ['name' => 'Lucky Streak',      'description' => '50 winning spins',              'requirement_type' => 'total_wins',   'requirement_value' => 50,     'reward_amount' => 250,   'icon' => '🔥'],
            ['name' => 'Big Winner',        'description' => 'Win 10,000 in one spin',        'requirement_type' => 'biggest_win',  'requirement_value' => 10000,  'reward_amount' => 1000,  'icon' => '⚡'],
            ['name' => 'Mega Winner',       'description' => 'Win 100,000 in one spin',       'requirement_type' => 'biggest_win',  'requirement_value' => 100000, 'reward_amount' => 10000, 'icon' => '☄️'],
            ['name' => 'Jackpot Hunter',    'description' => 'Win any jackpot',               'requirement_type' => 'jackpot_wins', 'requirement_value' => 1,      'reward_amount' => 5000,  'icon' => '💎'],
            ['name' => 'Cosmic Winner',     'description' => 'Win 5 jackpots',                'requirement_type' => 'jackpot_wins', 'requirement_value' => 5,      'reward_amount' => 50000, 'icon' => '⭐'],
            ['name' => 'High Roller',       'description' => 'Bet 100,000 total',             'requirement_type' => 'total_bet',    'requirement_value' => 100000, 'reward_amount' => 2000,  'icon' => '🪙'],
            ['name' => 'Free Spirit',       'description' => 'Use 10 free spins',             'requirement_type' => 'free_spins',   'requirement_value' => 10,     'reward_amount' => 100,   'icon' => '🎁'],
        ];

        foreach ($achievements as $a) {
            LotteryAchievement::updateOrCreate(['name' => $a['name']], $a);
        }

        $this->command->info('✅ Lottery achievements: ' . LotteryAchievement::count());
    }
}
