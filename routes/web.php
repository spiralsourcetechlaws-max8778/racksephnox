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
use App\Http\Controllers\MpesaController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/lottery.php';

Route::get('/', fn () => view('welcome'))->name('home');

Route::get('/terms',   [LegalPagesController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalPagesController::class, 'privacy'])->name('legal.privacy');
Route::get('/guide',   [GuideController::class, 'index'])->name('guide');
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

Route::middleware(['auth', 'verified'])->group(function () {

    // ── Dashboard ───────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Dashboard internal AJAX (session-authenticated JSON) ────
    Route::prefix('dashboard-api')->name('dashboard.api.')->group(function () {
        Route::get('/wallet-balance',       [DashboardController::class, 'walletBalance'])->name('wallet-balance');
        Route::get('/notifications-unread', [DashboardController::class, 'notificationsUnread'])->name('notifications-unread');
        Route::get('/btc-price',            [DashboardController::class, 'btcPrice'])->name('btc-price');
        Route::get('/lottery-jackpot',      [DashboardController::class, 'lotteryJackpot'])->name('lottery-jackpot');
        Route::get('/crypto-prices',        [DashboardController::class, 'cryptoPrices'])->name('crypto-prices');
    });

    // ── Profile ─────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/notifications', [ProfileController::class, 'updateNotificationPreferences'])->name('profile.notifications.update');
    Route::post('/profile/bank-account', [ProfileController::class, 'updateBankAccount'])->name('profile.bank-account.update');
    Route::get('/api/profile-data', [ProfileController::class, 'apiData'])->name('profile.api.data');

    // ── Investments (legacy) ────────────────────────────────────
    Route::get('/investments', [InvestmentController::class, 'index'])->name('investments.index');
    Route::get('/investments/{id}', [InvestmentController::class, 'show'])->name('investments.show');
    Route::post('/investments', [InvestmentController::class, 'redirectToMachines'])->name('investments.store');

    // ── Wallet ──────────────────────────────────────────────────
    Route::get('/wallet', [WalletController::class, 'show'])->name('wallet');

    // ── Transactions ────────────────────────────────────────────
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');

    // ── KYC ─────────────────────────────────────────────────────
    Route::get('/kyc', [KycController::class, 'index'])->name('kyc');
    Route::post('/kyc/upload', [KycController::class, 'upload'])->name('kyc.upload');

    // ── Trading ─────────────────────────────────────────────────
    Route::prefix('trading')->name('trading.')->group(function () {
        Route::get('/', [TradingController::class, 'index'])->name('index');
        Route::get('/price', [TradingController::class, 'price'])->name('price');
        Route::get('/candles/{interval}', [TradingController::class, 'candles'])->name('candles');
        Route::get('/order-book', [TradingController::class, 'orderBook'])->name('order-book');
        Route::get('/analytics', [TradingController::class, 'analytics'])->name('analytics');
        Route::post('/buy', [TradingController::class, 'buy'])->name('buy');
        Route::post('/sell', [TradingController::class, 'sell'])->name('sell');
        Route::post('/orders/{order}/cancel', [TradingController::class, 'cancelOrder'])->name('cancel');
        Route::post('/transfer/in', [TradingController::class, 'transferToTrading'])->name('transfer.in');
        Route::post('/transfer/out', [TradingController::class, 'transferFromTrading'])->name('transfer.out');
    });

    // ── Social Trading ──────────────────────────────────────────
    Route::prefix('social-trading')->name('social-trading.')->group(function () {
        Route::get('/leaderboard', [App\Http\Controllers\SocialTradingController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/followed', [App\Http\Controllers\SocialTradingController::class, 'followed'])->name('followed');
        Route::get('/copy-history', [App\Http\Controllers\SocialTradingController::class, 'copyHistory'])->name('copy-history');
        Route::get('/profile/edit', [App\Http\Controllers\SocialTradingController::class, 'editProfile'])->name('profile.edit');
        Route::post('/profile/update', [App\Http\Controllers\SocialTradingController::class, 'updateProfile'])->name('profile.update');
        Route::post('/follow/{trader}', [App\Http\Controllers\SocialTradingController::class, 'follow'])->name('follow');
        Route::delete('/unfollow/{trader}', [App\Http\Controllers\SocialTradingController::class, 'unfollow'])->name('unfollow');
        Route::post('/settings/{trader}', [App\Http\Controllers\SocialTradingController::class, 'updateSettings'])->name('settings.update');
        Route::get('/profile/{username}', [App\Http\Controllers\SocialTradingController::class, 'traderProfile'])->name('profile');
    });

    // ── Referrals ───────────────────────────────────────────────
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals');

    // ── Deposits ────────────────────────────────────────────────
    Route::get('/deposit', [DepositController::class, 'form'])->name('deposit.form');
    Route::post('/deposit', [DepositController::class, 'submit'])->name('deposit.submit');

    // ── Withdrawals ─────────────────────────────────────────────
    Route::get('/withdraw', [WithdrawalController::class, 'form'])->name('withdrawal.form');
    Route::post('/withdraw', [WithdrawalController::class, 'submit'])->name('withdrawal.submit');

    // ── M-Pesa ──────────────────────────────────────────────────
    Route::prefix('mpesa')->name('mpesa.')->group(function () {
        Route::get('/deposit', [MpesaController::class, 'showDepositForm'])->name('deposit');
        Route::post('/deposit/initiate', [MpesaController::class, 'initiateDeposit'])->name('deposit.initiate');
        Route::get('/withdraw', [MpesaController::class, 'showWithdrawalForm'])->name('withdraw');
        Route::post('/withdraw/initiate', [MpesaController::class, 'initiateWithdrawal'])->name('withdraw.initiate');
    });

    // ── Bank Accounts ───────────────────────────────────────────
    Route::resource('bank-accounts', BankAccountController::class)->except(['show']);

    // ── Notifications ───────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('markAllRead');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/', [NotificationController::class, 'destroyAll'])->name('destroyAll');
        Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
    });

    // ── Loans ───────────────────────────────────────────────────
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [App\Http\Controllers\LoanController::class, 'index'])->name('index');
        Route::get('/apply/{product}', [App\Http\Controllers\LoanController::class, 'create'])->name('apply');
        Route::post('/apply/{product}', [App\Http\Controllers\LoanController::class, 'store'])->name('store');
        Route::post('/preview/{product}', [App\Http\Controllers\LoanController::class, 'preview'])->name('preview');
        Route::get('/{loan}', [App\Http\Controllers\LoanController::class, 'show'])->name('show');
        Route::post('/{loan}/repay', [App\Http\Controllers\LoanController::class, 'repay'])->name('repay');
        Route::post('/{loan}/cancel', [App\Http\Controllers\LoanController::class, 'cancel'])->name('cancel');
    });

    // ── Machines ────────────────────────────────────────────────
    Route::prefix('machines')->name('machines.')->group(function () {
        Route::get('/', [MachineController::class, 'index'])->name('index');
        Route::get('/my-investments', [MachineController::class, 'myInvestments'])->name('my-investments');
        Route::get('/status/{investment}', [MachineController::class, 'status'])->name('status');
        Route::post('/{investment}/early-withdraw', [MachineController::class, 'earlyWithdraw'])->name('early-withdraw');
        Route::post('/{machine}/invest', [MachineController::class, 'invest'])->name('invest');
        Route::get('/{code}', [MachineController::class, 'show'])->name('show');
    });
});
