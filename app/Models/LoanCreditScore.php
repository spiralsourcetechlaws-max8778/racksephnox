<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanCreditScore extends Model
{
    use HasFactory;

    protected $table = 'loan_credit_scores';

    protected $fillable = [
        'user_id', 'score', 'tier',
        'total_borrowed', 'total_repaid',
        'on_time_payments', 'late_payments', 'defaults',
        'last_calculated_at',
    ];

    protected $casts = [
        'score'              => 'integer',
        'total_borrowed'     => 'float',
        'total_repaid'       => 'float',
        'on_time_payments'   => 'integer',
        'late_payments'      => 'integer',
        'defaults'           => 'integer',
        'last_calculated_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function getTierAttribute($value): string
    {
        return $this->scoreTier($this->score);
    }

    public function scoreTier(int $score): string
    {
        return match (true) {
            $score >= 800 => 'Diamond',
            $score >= 700 => 'Platinum',
            $score >= 600 => 'Gold',
            $score >= 500 => 'Silver',
            default       => 'Bronze',
        };
    }
}
