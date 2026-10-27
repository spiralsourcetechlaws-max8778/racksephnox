<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BtcPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = ['price', 'price_kes', 'currency', 'recorded_at'];

    protected $casts = [
        'price'       => 'decimal:8',
        'price_kes'   => 'decimal:2',
        'recorded_at' => 'datetime',
    ];
}
