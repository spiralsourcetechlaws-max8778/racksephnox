<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryJackpotWin extends Model
{
    use HasFactory;

    protected $table = 'lottery_jackpot_wins';

    protected $fillable = [
        'user_id', 'lottery_spin_id', 'tier',
        'amount_won', 'pool_before', 'pool_after', 'paid', 'paid_at',
    ];

    protected $casts = [
        'amount_won'  => 'float',
        'pool_before' => 'float',
        'pool_after'  => 'float',
        'paid'        => 'boolean',
        'paid_at'     => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function spin()
    {
        return $this->belongsTo(LotterySpin::class, 'lottery_spin_id');
    }
}
