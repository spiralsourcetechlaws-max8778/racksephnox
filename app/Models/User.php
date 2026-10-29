<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'referral_code', 'referred_by',
        'kyc_status', 'is_active',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        'is_admin', 'admin_role', 'kyc_level', 'is_verified', 'onboarding_completed',
        'avatar', 'notification_preferences', 'free_spins_available',
        'is_vip', 'has_promo', 'promo_expires_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_verified' => 'boolean',
        'onboarding_completed' => 'boolean',
        'notification_preferences' => 'array',
        'free_spins_available' => 'integer',
        'is_vip' => 'boolean',
        'has_promo' => 'boolean',
        'promo_expires_at' => 'datetime',
    ];

    public function hasRole(string ...$roles): bool
    {
        if (!$this->is_admin) return false;
        $role = $this->admin_role ?? 'super_admin';
        return empty($roles) || in_array($role, $roles);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_admin && ($this->admin_role === 'super_admin' || $this->admin_role === null);
    }

    public function isFinanceAdmin(): bool
    {
        return $this->is_admin && $this->admin_role === 'finance';
    }

    public function isSupportAdmin(): bool
    {
        return $this->is_admin && $this->admin_role === 'support';
    }

    public function getRoleLabelAttribute(): string
    {
        if (!$this->is_admin) return 'User';
        return match ($this->admin_role ?? 'super_admin') {
            'super_admin' => 'Super Admin',
            'finance'     => 'Finance Admin',
            'support'     => 'Support Admin',
            default       => 'Admin',
        };
    }

    protected static function booted()
    {
        static::created(function ($user) {
            try {
                if (empty($user->referral_code)) {
                    $user->referral_code = strtoupper(Str::random(8));
                    $user->saveQuietly();
                }
                if (Schema::hasTable('wallets') && !$user->wallet) {
                    $wallet = $user->wallet()->create(['user_id' => $user->id, 'balance' => 0]);
                    $wallet->credit(60, 'Welcome bonus');
                }
                if (Schema::hasTable('trading_accounts') && !$user->tradingAccount) {
                    $user->tradingAccount()->create(['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]);
                }
                if (Schema::hasTable('trading_profiles') && !$user->tradingProfile) {
                    $user->tradingProfile()->create([
                        'username' => 'trader_' . $user->id,
                        'is_public' => true,
                        'allow_copy_trading' => true,
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('User booted creation failed: ' . $e->getMessage());
            }
        });
    }

    public function wallet() { return $this->hasOne(Wallet::class); }
    public function investments() { return $this->hasMany(Investment::class); }
    public function transactions() { return $this->hasMany(Transaction::class); }
    public function mpesaTransactions() { return $this->hasMany(MpesaTransaction::class); }
    public function kycDocuments() { return $this->hasMany(KycDocument::class); }
    public function auditLogs() { return $this->hasMany(AuditLog::class); }
    public function referrals() { return $this->hasMany(User::class, 'referred_by'); }
    public function machineInvestments() { return $this->hasMany(MachineInvestment::class); }
    public function tradingAccount() { return $this->hasOne(TradingAccount::class); }
    public function tradeOrders() { return $this->hasMany(TradeOrder::class); }
    public function depositRequests() { return $this->hasMany(DepositRequest::class); }
    public function withdrawalRequests() { return $this->hasMany(WithdrawalRequest::class); }
    public function bankAccounts() { return $this->hasMany(UserBankAccount::class); }
    public function lotterySpins() { return $this->hasMany(LotterySpin::class); }
    public function lotteryAchievements() {
        return $this->belongsToMany(LotteryAchievement::class, 'lottery_user_achievements')
                    ->withPivot('achieved_at')->withTimestamps();
    }
    public function tradingProfile() { return $this->hasOne(TradingProfile::class); }
    public function followedTraders() {
        return $this->belongsToMany(User::class, 'followed_traders', 'follower_id', 'trader_id')
                    ->withPivot('copy_ratio', 'auto_copy', 'max_copy_amount')->withTimestamps();
    }
    public function followers() {
        return $this->belongsToMany(User::class, 'followed_traders', 'trader_id', 'follower_id')
                    ->withPivot('copy_ratio', 'auto_copy', 'max_copy_amount')->withTimestamps();
    }
    public function tradingBonusTracker() { return $this->hasOne(\App\Models\TradingBonusTracker::class); }
    public function loans() { return $this->hasMany(Loan::class); }
    public function activeLoans() { return $this->hasMany(Loan::class)->where('status', 'active'); }
    public function loanRepayments() { return $this->hasMany(LoanRepayment::class); }
    public function guaranteedLoans() { return $this->hasMany(LoanGuarantor::class); }
    public function loanCreditScore() { return $this->hasOne(LoanCreditScore::class); }
}
