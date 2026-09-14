<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryGuild extends Model
{
    use HasFactory;

    protected $table = 'lottery_guilds';

    protected $fillable = ['name', 'description', 'owner_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(LotteryGuildMember::class);
    }

    public function tournaments()
    {
        return $this->hasMany(LotteryGuildTournament::class);
    }

    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
