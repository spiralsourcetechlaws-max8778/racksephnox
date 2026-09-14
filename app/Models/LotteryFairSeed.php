<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryFairSeed extends Model
{
    use HasFactory;

    protected $table = 'lottery_fair_seeds';

    protected $fillable = [
        'user_id', 'server_seed_hash', 'server_seed', 'client_seed',
        'nonce', 'previous_hash', 'revealed', 'revealed_at',
    ];

    protected $casts = [
        'nonce'       => 'integer',
        'revealed'    => 'boolean',
        'revealed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($q)
    {
        return $q->where('revealed', false);
    }
}
