<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingProfile extends Model
{
    use HasFactory;

    protected $table = 'trading_profiles';

    protected $fillable = [
        'user_id', 'username', 'display_name', 'bio', 'avatar',
        'is_public', 'allow_copy_trading', 'copy_ratio',
        'total_trades', 'winning_trades', 'total_profit',
    ];

    protected $casts = [
        'is_public'          => 'boolean',
        'allow_copy_trading' => 'boolean',
        'copy_ratio'         => 'float',
        'total_trades'       => 'integer',
        'winning_trades'     => 'integer',
        'total_profit'       => 'float',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function getWinRateAttribute(): float
    {
        $total = (int) ($this->total_trades ?? 0);
        if ($total <= 0) return 0.0;
        $wins = max(0, min((int) ($this->winning_trades ?? 0), $total));
        return round(($wins / $total) * 100, 2);
    }

    public function getLosingTradesAttribute(): int
    {
        $total = (int) ($this->total_trades ?? 0);
        $wins  = (int) ($this->winning_trades ?? 0);
        return max(0, $total - $wins);
    }

    public function getDisplayNameAttribute($value): string
    {
        return $value ?: ($this->user->name ?? ('Trader #' . ($this->user_id ?? '?')));
    }

    public function getRankBadgeAttribute(): string
    {
        $rate = $this->win_rate;
        return match (true) {
            $rate >= 80 => '🥇 Elite',
            $rate >= 60 => '🥈 Pro',
            $rate >= 40 => '🥉 Rising',
            default     => '🪙 Rookie',
        };
    }

    public function getFormattedProfitAttribute(): string
    {
        $profit = (float) ($this->total_profit ?? 0);
        $sign = $profit >= 0 ? '+' : '-';
        return $sign . 'KES ' . number_format(abs($profit), 2);
    }

    public function scopePublic($q)     { return $q->where('is_public', true); }
    public function scopeCopyable($q)   { return $q->where('allow_copy_trading', true); }
    public function scopeTopTraders($q) { return $q->orderByDesc('total_profit'); }
}
