<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryGuildMember extends Model
{
    use HasFactory;

    protected $table = 'lottery_guild_members';

    protected $fillable = [
        'lottery_guild_id', 'user_id', 'role', 'joined_at',
    ];

    protected $casts = ['joined_at' => 'datetime'];

    public function guild()
    {
        return $this->belongsTo(LotteryGuild::class, 'lottery_guild_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsLeaderAttribute(): bool
    {
        return $this->role === 'leader';
    }
}
