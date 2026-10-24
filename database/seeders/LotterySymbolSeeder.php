<?php

namespace Database\Seeders;

use App\Models\LotterySymbol;
use Illuminate\Database\Seeder;

class LotterySymbolSeeder extends Seeder
{
    public function run(): void
    {
        // 8 sacred symbols — names must match the JS emoji map
        $symbols = [
            // ── Divine tier (highest value) ──
            ['name' => 'seven',   'display_name' => 'Sacred Seven', 'icon' => '7️⃣', 'multiplier' => 50.00, 'is_divine' => true],
            ['name' => 'crown',   'display_name' => 'Crown',        'icon' => '👑', 'multiplier' => 30.00, 'is_divine' => true],
            ['name' => 'diamond', 'display_name' => 'Diamond',      'icon' => '💎', 'multiplier' => 25.00, 'is_divine' => true],

            // ── Mid tier ──
            ['name' => 'star',    'display_name' => 'Star',         'icon' => '⭐', 'multiplier' => 15.00, 'is_divine' => false],
            ['name' => 'bell',    'display_name' => 'Bell',         'icon' => '🔔', 'multiplier' => 10.00, 'is_divine' => false],
            ['name' => 'gem',     'display_name' => 'Gem',          'icon' => '💎', 'multiplier' => 8.00,  'is_divine' => false],

            // ── Low tier ──
            ['name' => 'coin',    'display_name' => 'Coin',         'icon' => '🪙', 'multiplier' => 5.00,  'is_divine' => false],
            ['name' => 'clover',  'display_name' => 'Clover',       'icon' => '🍀', 'multiplier' => 3.00,  'is_divine' => false],
            ['name' => 'cherry',  'display_name' => 'Cherry',       'icon' => '🍒', 'multiplier' => 2.00,  'is_divine' => false],
            ['name' => 'lemon',   'display_name' => 'Lemon',        'icon' => '🍋', 'multiplier' => 1.50,  'is_divine' => false],
        ];

        foreach ($symbols as $s) {
            LotterySymbol::updateOrCreate(['name' => $s['name']], $s);
        }

        $this->command->info('✅ Lottery symbols: ' . LotterySymbol::count());
    }
}
