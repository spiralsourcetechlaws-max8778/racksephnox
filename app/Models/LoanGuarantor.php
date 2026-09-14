<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanGuarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'user_id', 'amount_guaranteed', 'status', 'accepted_at',
    ];

    protected $casts = [
        'amount_guaranteed' => 'float',
        'accepted_at'       => 'datetime',
    ];

    public function loan() { return $this->belongsTo(Loan::class); }
    public function user() { return $this->belongsTo(User::class); }
}
