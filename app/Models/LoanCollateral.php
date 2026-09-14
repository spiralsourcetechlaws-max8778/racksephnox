<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanCollateral extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'type', 'description', 'estimated_value', 'document_path', 'status',
    ];

    protected $casts = [
        'estimated_value' => 'float',
    ];

    public function loan() { return $this->belongsTo(Loan::class); }
}
