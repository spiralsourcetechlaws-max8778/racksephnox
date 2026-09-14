<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryTournament extends Model
{
    use HasFactory;

    protected $table = 'lottery_tournaments';

    protected $fillable = [
        'name', 'description', 'start_date', 'end_date',
        'is_active', 'prize_pool', 'prize_distributed',
    ];

    protected $casts = [
        'start_date'         => 'datetime',
        'end_date'           => 'datetime',
        'is_active'          => 'boolean',
        'prize_pool'         => 'float',
        'prize_distributed'  => 'boolean',
    ];

    public function entries()
    {
        return $this->hasMany(LotteryTournamentEntry::class);
    }

    public function topEntries($limit = 10)
    {
        return $this->entries()->orderByDesc('score')->limit($limit);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)
                 ->where('start_date', '<=', now())
                 ->where('end_date', '>=', now());
    }

    public function scopeUpcoming($q)
    {
        return $q->where('start_date', '>', now());
    }

    public function getIsLiveAttribute(): bool
    {
        return $this->start_date <= now() && $this->end_date >= now();
    }

    public function getPrizeStructureAttribute(): array
    {
        return [
            1 => $this->prize_pool * 0.40,
            2 => $this->prize_pool * 0.25,
            3 => $this->prize_pool * 0.15,
            4 => $this->prize_pool * 0.08,
            5 => $this->prize_pool * 0.05,
            6 => $this->prize_pool * 0.03,
            7 => $this->prize_pool * 0.02,
            8 => $this->prize_pool * 0.01,
            9 => $this->prize_pool * 0.005,
            10 => $this->prize_pool * 0.005,
        ];
    }
}
