<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color',
        'min_amount', 'max_amount', 'interest_rate', 'interest_method',
        'min_duration_days', 'max_duration_days', 'grace_period_days',
        'late_fee_rate', 'requires_guarantor', 'requires_collateral',
        'min_credit_score', 'is_active', 'frequency_hz',
    ];

    protected $casts = [
        'min_amount'          => 'float',
        'max_amount'          => 'float',
        'interest_rate'       => 'float',
        'late_fee_rate'       => 'float',
        'min_duration_days'   => 'integer',
        'max_duration_days'   => 'integer',
        'grace_period_days'   => 'integer',
        'min_credit_score'    => 'integer',
        'requires_guarantor'  => 'boolean',
        'requires_collateral' => 'boolean',
        'is_active'           => 'boolean',
        'frequency_hz'        => 'integer',
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function scopeActive($q) { return $q->where('is_active', true); }

    public function getRangeLabelAttribute(): string
    {
        return 'KES ' . number_format($this->min_amount, 0)
             . ' – KES ' . number_format($this->max_amount, 0);
    }
}
