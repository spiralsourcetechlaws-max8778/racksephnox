<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryBonusWheel extends Model
{
    use HasFactory;

    protected $table = 'lottery_bonus_wheels';

    protected $fillable = ['name', 'segments', 'is_active'];

    protected $casts = [
        'segments'  => 'array',
        'is_active' => 'boolean',
    ];

    public function spins()
    {
        return $this->hasMany(LotteryBonusWheelSpin::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function pickRandomSegment(): array
    {
        $segments = $this->segments ?? [];
        if (empty($segments)) return ['type' => 'empty', 'amount' => 0];

        $totalWeight = array_sum(array_column($segments, 'weight'));
        $roll = mt_rand(1, max(1, (int) $totalWeight));
        $cursor = 0;
        foreach ($segments as $seg) {
            $cursor += (int) $seg['weight'];
            if ($roll <= $cursor) return $seg;
        }
        return $segments[0];
    }
}
