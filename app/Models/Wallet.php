<?php

namespace App\Models;

use App\Events\WalletBalanceUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'balance', 'locked_balance'];
    protected $casts = ['balance' => 'decimal:2', 'locked_balance' => 'decimal:2'];

    public function user() { return $this->belongsTo(User::class); }
    public function transactions() { return $this->hasMany(Transaction::class); }

    public function credit($amount, $description, $type = 'credit')
    {
        if ($amount <= 0) throw new \InvalidArgumentException('Credit amount must be positive.');

        return DB::transaction(function () use ($amount, $description, $type) {
            // Lock row for update to prevent race conditions
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();
            $wallet->increment('balance', $amount);

            return $wallet->transactions()->create([
                'user_id'       => $wallet->user_id,
                'wallet_id'     => $wallet->id,
                'type'          => $type,
                'amount'        => $amount,
                'balance_after' => $wallet->balance,
                'description'   => $description,
                'status'        => 'completed',
            ]);
        });
    }

    public function debit($amount, $description, $type = 'debit')
    {
        if ($amount <= 0) throw new \InvalidArgumentException('Debit amount must be positive.');

        return DB::transaction(function () use ($amount, $description, $type) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                throw new \Exception('Insufficient balance');
            }

            $wallet->decrement('balance', $amount);

            return $wallet->transactions()->create([
                'user_id'       => $wallet->user_id,
                'wallet_id'     => $wallet->id,
                'type'          => $type,
                'amount'        => -$amount,
                'balance_after' => $wallet->balance,
                'description'   => $description,
                'status'        => 'completed',
            ]);
        });
    }

    protected static function booted()
    {
        static::updated(function ($wallet) {
            if ($wallet->isDirty('balance') || $wallet->isDirty('locked_balance')) {
                try {
                    broadcast(new WalletBalanceUpdated($wallet));
                } catch (\Throwable $e) {
                    // Fail silently if Reverb is not configured on Render
                }
            }
        });
    }
}
