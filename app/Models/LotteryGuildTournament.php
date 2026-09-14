<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryGuildTournament extends Model
{
    use HasFactory;

    protected $table = 'lottery_guild_tournaments';

    protected $fillable = [
        'lottery_guild_id', 'lottery_tournament_id', 'score',
    ];

    protected $casts = ['score' => 'integer'];

    public function guild()
    {
        return $this->belongsTo(LotteryGuild::class, 'lottery_guild_id');
    }

    public function tournament()
    {
        return $this->belongsTo(LotteryTournament::class, 'lottery_tournament_id');
    }
}
