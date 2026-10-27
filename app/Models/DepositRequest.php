<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'amount',
        'transaction_reference',
        'status',
        'admin_notes',
        'verified_at',
    ];

    protected $casts = [
        'amount'      => 'float',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($q)   { return $q->where('status', 'pending'); }
    public function scopeApproved($q)  { return $q->where('status', 'approved'); }
    public function scopeRejected($q)  { return $q->where('status', 'rejected'); }
}
