<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowedTrader extends Model
{
    use HasFactory;

    protected $table = 'followed_traders';

    protected $fillable = [
        'follower_id', 'trader_id', 'copy_ratio', 'auto_copy', 'max_copy_amount',
    ];

    protected $casts = [
        'copy_ratio'      => 'float',
        'auto_copy'       => 'boolean',
        'max_copy_amount' => 'float',
    ];

    public function follower() { return $this->belongsTo(User::class, 'follower_id'); }
    public function trader()   { return $this->belongsTo(User::class, 'trader_id'); }
}
