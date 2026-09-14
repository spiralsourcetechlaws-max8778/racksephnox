<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryTournamentEntry extends Model
{
    use HasFactory;

    protected $table = 'lottery_tournament_entries';

    protected $fillable = [
        'lottery_tournament_id', 'user_id', 'score', 'rank',
    ];

    protected $casts = [
        'score' => 'integer',
        'rank'  => 'integer',
    ];

    public function tournament()
    {
        return $this->belongsTo(LotteryTournament::class, 'lottery_tournament_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
