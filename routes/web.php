<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LegalPagesController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/lottery.php';

// Public home
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Legal pages
Route::get('/terms', [LegalPagesController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalPagesController::class, 'privacy'])->name('legal.privacy');
Route::get('/guide', [GuideController::class, 'index'])->name('guide');

// Language switcher
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

// Authenticated & verified routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/notifications', [ProfileController::class, 'updateNotificationPreferences'])->name('profile.notifications.update');
    Route::post('/profile/bank-account', [ProfileController::class, 'updateBankAccount'])->name('profile.bank-account.update');
    Route::get('/api/profile-data', [ProfileController::class, 'apiData'])->name('profile.api.data');

    // Investments (legacy + unified)
    Route::get('/investments', [InvestmentController::class, 'index'])->name('investments.index');
    Route::get('/investments/{id}', [InvestmentController::class, 'show'])->name('investments.show');
    Route::post('/investments', [InvestmentController::class, 'redirectToMachines'])->name('investments.store');

    // Wallet
    Route::get('/wallet', [WalletController::class, 'show'])->name('wallet');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');

    // KYC
    Route::get('/kyc', [KycController::class, 'index'])->name('kyc');
    Route::post('/kyc/upload', [KycController::class, 'upload'])->name('kyc.upload');

    // Trading (advanced)
    Route::prefix('trading')->name('trading.')->group(function () {
        Route::get('/', [TradingController::class, 'index'])->name('index');
        Route::post('/buy', [TradingController::class, 'buy'])->name('buy');
        Route::post('/sell', [TradingController::class, 'sell'])->name('sell');
        Route::post('/orders/{order}/cancel', [TradingController::class, 'cancelOrder'])->name('cancel');
        Route::get('/order-book', [TradingController::class, 'orderBook'])->name('order-book');
        Route::get('/candles/{interval}', [TradingController::class, 'candles'])->name('candles');
        Route::get('/analytics', [TradingController::class, 'analytics'])->name('analytics');
    });

    // Social Trading
    Route::prefix('social-trading')->name('social-trading.')->group(function () {
        Route::get('/leaderboard', [App\Http\Controllers\SocialTradingController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/profile/{username}', [App\Http\Controllers\SocialTradingController::class, 'traderProfile'])->name('profile');
        Route::get('/profile/edit', [App\Http\Controllers\SocialTradingController::class, 'editProfile'])->name('profile.edit');
        Route::post('/profile/update', [App\Http\Controllers\SocialTradingController::class, 'updateProfile'])->name('profile.update');
        Route::post('/follow/{trader}', [App\Http\Controllers\SocialTradingController::class, 'follow'])->name('follow');
        Route::delete('/unfollow/{trader}', [App\Http\Controllers\SocialTradingController::class, 'unfollow'])->name('unfollow');
        Route::get('/followed', [App\Http\Controllers\SocialTradingController::class, 'followed'])->name('followed');
        Route::post('/settings/{trader}', [App\Http\Controllers\SocialTradingController::class, 'updateSettings'])->name('settings.update');
        Route::get('/copy-history', [App\Http\Controllers\SocialTradingController::class, 'copyHistory'])->name('copy-history');
    });

    // Referrals
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals');

    // Deposit (web forms)
    Route::get('/deposit', [DepositController::class, 'form'])->name('deposit.form');
    Route::post('/deposit', [DepositController::class, 'submit'])->name('deposit.submit');

    // Withdrawal (web forms)
    Route::get('/withdraw', [WithdrawalController::class, 'form'])->name('withdrawal.form');
    Route::post('/withdraw', [WithdrawalController::class, 'submit'])->name('withdrawal.submit');

    // Bank Accounts
    Route::resource('bank-accounts', BankAccountController::class)->except(['show']);

    // Notifications (web)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('markAllRead');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/', [NotificationController::class, 'destroyAll'])->name('destroyAll');
        Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
    });


    // ==================== LOANS DOMAIN ====================
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [App\Http\Controllers\LoanController::class, 'index'])->name('index');
        Route::get('/apply/{product}', [App\Http\Controllers\LoanController::class, 'create'])->name('apply');
        Route::post('/apply/{product}', [App\Http\Controllers\LoanController::class, 'store'])->name('store');
        Route::post('/preview/{product}', [App\Http\Controllers\LoanController::class, 'preview'])->name('preview');
        Route::get('/{loan}', [App\Http\Controllers\LoanController::class, 'show'])->name('show');
        Route::post('/{loan}/repay', [App\Http\Controllers\LoanController::class, 'repay'])->name('repay');
        Route::post('/{loan}/cancel', [App\Http\Controllers\LoanController::class, 'cancel'])->name('cancel');
    });

    // Machines (web)
    Route::prefix('machines')->name('machines.')->group(function () {
        Route::get('/', [MachineController::class, 'index'])->name('index');
        Route::get('/{code}', [MachineController::class, 'show'])->name('show');
        Route::post('/{machine}/invest', [MachineController::class, 'invest'])->name('invest');
        Route::get('/my-investments', [MachineController::class, 'myInvestments'])->name('my-investments');
        Route::post('/{investment}/early-withdraw', [MachineController::class, 'earlyWithdraw'])->name('early-withdraw');
        Route::get('/status/{investment}', [MachineController::class, 'status'])->name('status');
    });
});

/* ==================== TRADING ROUTES ==================== */
// NOTE: these are already inside the auth+verified group.
Route::prefix('trading')->name('trading.')->group(function () {
    Route::get('/',                    [App\Http\Controllers\TradingController::class, 'index'])->name('index');
    Route::post('/buy',                [App\Http\Controllers\TradingController::class, 'buy'])->name('buy');
    Route::post('/sell',               [App\Http\Controllers\TradingController::class, 'sell'])->name('sell');
    Route::post('/orders/{order}/cancel', [App\Http\Controllers\TradingController::class, 'cancelOrder'])->name('cancel');
    Route::get('/order-book',          [App\Http\Controllers\TradingController::class, 'orderBook'])->name('order-book');
    Route::get('/candles/{interval?}', [App\Http\Controllers\TradingController::class, 'candles'])->name('candles');
});
