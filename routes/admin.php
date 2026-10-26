<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\InvestmentPlanController;
use App\Http\Controllers\Admin\KycController;
use App\Http\Controllers\Admin\DepositVerificationController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\LotteryController;
use App\Http\Controllers\Admin\LotteryAnalyticsController;
use App\Http\Controllers\Admin\LoanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');

    // Users
    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::post('users/{user}/toggle-vip', [UserController::class, 'toggleVip'])->name('users.toggle-vip');
    Route::post('users/{user}/toggle-promo', [UserController::class, 'togglePromo'])->name('users.toggle-promo');
    Route::post('users/export', [UserController::class, 'export'])->name('users.export');

    // Investment Plans
    Route::resource('plans', InvestmentPlanController::class);
    Route::post('plans/export', [InvestmentPlanController::class, 'export'])->name('plans.export');

    // KYC
    Route::get('kyc', [KycController::class, 'index'])->name('kyc.index');
    Route::get('kyc/{document}', [KycController::class, 'show'])->name('kyc.show');
    Route::post('kyc/{document}/approve', [KycController::class, 'approve'])->name('kyc.approve');
    Route::post('kyc/{document}/reject', [KycController::class, 'reject'])->name('kyc.reject');

    // Deposits
    Route::get('deposits', [DepositVerificationController::class, 'index'])->name('deposits.index');
    Route::post('deposits/{deposit}/verify', [DepositVerificationController::class, 'verify'])->name('deposits.verify');
    Route::post('deposits/{deposit}/reject', [DepositVerificationController::class, 'reject'])->name('deposits.reject');

    // Withdrawals
    Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('withdrawals/{withdrawal}/process', [WithdrawalController::class, 'process'])->name('withdrawals.process');
    Route::post('withdrawals/{withdrawal}/complete', [WithdrawalController::class, 'complete'])->name('withdrawals.complete');
    Route::post('withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/cache-clear', [SettingsController::class, 'clearCache'])->name('settings.cache-clear');
    Route::post('settings/maintenance', [SettingsController::class, 'toggleMaintenance'])->name('settings.maintenance');

    // ==================== ADMIN · LOANS ====================
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/{loan}', [LoanController::class, 'show'])->name('show');
        Route::post('/{loan}/approve', [LoanController::class, 'approve'])->name('approve');
        Route::post('/{loan}/reject', [LoanController::class, 'reject'])->name('reject');
        Route::post('/{loan}/disburse', [LoanController::class, 'disburse'])->name('disburse');
    });

    // ==================== ADMIN · LOTTERY ====================
    Route::prefix('lottery')->name('lottery.')->group(function () {
        // Config & Analytics
        Route::get('/', [LotteryController::class, 'index'])->name('index');
        Route::get('/analytics', [LotteryAnalyticsController::class, 'index'])->name('analytics');
        Route::post('/rtp-simulate', [LotteryController::class, 'rtpSimulate'])->name('rtp-simulate');
        Route::get('/export', [LotteryController::class, 'exportConfig'])->name('export');
        Route::post('/import', [LotteryController::class, 'importConfig'])->name('import');

        // Symbols
        Route::get('/symbols', [LotteryController::class, 'symbols'])->name('symbols');
        Route::post('/symbols', [LotteryController::class, 'updateSymbols'])->name('symbols.update');
        
        // Jackpots
        Route::get('/jackpots', [LotteryController::class, 'jackpots'])->name('jackpots');
        Route::put('/jackpots/{pool}', [LotteryController::class, 'updateJackpot'])->name('jackpots.update');
        Route::post('/jackpots/{pool}/force-drop', [LotteryController::class, 'forceJackpotDrop'])->name('jackpots.force-drop');
        Route::post('/jackpots/{pool}/reset', [LotteryController::class, 'resetJackpot'])->name('jackpots.reset');

        // Games Edit
        Route::get('/{lottery}/edit', [LotteryController::class, 'edit'])->name('edit');
        Route::put('/{lottery}', [LotteryController::class, 'update'])->name('update');
    });

});
