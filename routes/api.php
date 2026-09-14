<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\MpesaController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\TradingController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\WithdrawalController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\CryptoController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\LotteryController as ApiLotteryController;
use App\Http\Controllers\Api\V2\MachineController as V2MachineController;
use App\Http\Controllers\Api\V2\PortfolioController;
use App\Http\Controllers\Api\V2\WealthTaxController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'timestamp' => now()]);
});

// API v1
Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/mpesa/callback', [MpesaController::class, 'callback'])->name('api.mpesa.callback');
    Route::get('/crypto/prices', [CryptoController::class, 'prices']);
    Route::get('/machine/list', [MachineController::class, 'publicList']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);
        Route::put('/user/password', [AuthController::class, 'updatePassword']);

        // Wallet
        Route::prefix('wallet')->group(function () {
            Route::get('/balance', [WalletController::class, 'balance']);
            Route::get('/transactions', [WalletController::class, 'transactions']);
            Route::post('/transfer', [WalletController::class, 'transfer']);
            Route::get('/summary', [WalletController::class, 'summary']);
        });

        // Trading API
        Route::prefix('trading')->group(function () {
            Route::get('/balance', [TradingController::class, 'balance']);
            Route::get('/price', [TradingController::class, 'price']);
            Route::post('/buy', [TradingController::class, 'buy']);
            Route::post('/sell', [TradingController::class, 'sell']);
            Route::get('/orders', [TradingController::class, 'orders']);
        });

        // Machines API
        Route::prefix('machines')->group(function () {
            Route::get('/', [MachineController::class, 'index']);
            Route::get('/{code}', [MachineController::class, 'show']);
            Route::post('/{machine}/invest', [MachineController::class, 'invest']);
            Route::get('/{investment}/status', [MachineController::class, 'status']);
            Route::get('/my-investments', [MachineController::class, 'myInvestments']);
        });

        // KYC API
        Route::prefix('kyc')->group(function () {
            Route::get('/status', [KycController::class, 'status']);
            Route::post('/upload', [KycController::class, 'upload']);
            Route::post('/verify-id', [KycController::class, 'verifyId']);
        });

        // Deposit API
        Route::prefix('deposit')->group(function () {
            Route::get('/pochi-number', [DepositController::class, 'getPochiNumber']);
            Route::post('/submit', [DepositController::class, 'submitRequest']);
            Route::get('/history', [DepositController::class, 'history']);
            Route::get('/status/{id}', [DepositController::class, 'status']);
            Route::post('/stk', [DepositController::class, 'stkPush']);
        });

        // Withdrawal API
        Route::prefix('withdrawal')->group(function () {
            Route::post('/submit', [WithdrawalController::class, 'submitRequest']);
            Route::get('/history', [WithdrawalController::class, 'history']);
            Route::get('/status/{id}', [WithdrawalController::class, 'status']);
            Route::post('/bank-account', [WithdrawalController::class, 'addBankAccount']);
            Route::delete('/bank-account/{id}', [WithdrawalController::class, 'removeBankAccount']);
        });

        // Transactions API
        Route::prefix('transactions')->group(function () {
            Route::get('/', [TransactionController::class, 'index']);
            Route::get('/export', [TransactionController::class, 'export']);
            Route::get('/summary', [TransactionController::class, 'summary']);
            Route::get('/types', [TransactionController::class, 'types']);
        });

        // Referrals API
        Route::prefix('referrals')->group(function () {
            Route::get('/stats', [ReferralController::class, 'stats']);
            Route::get('/list', [ReferralController::class, 'list']);
            Route::get('/bonuses', [ReferralController::class, 'bonuses']);
            Route::get('/link', [ReferralController::class, 'getLink']);
        });

        // Crypto API
        Route::prefix('crypto')->group(function () {
            Route::get('/prices', [CryptoController::class, 'prices']);
            Route::get('/history', [CryptoController::class, 'history']);
            Route::get('/market', [CryptoController::class, 'marketData']);
        });

        // Notifications API
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::delete('/', [NotificationController::class, 'destroyAll']);
            Route::get('/preferences', [NotificationController::class, 'preferences']);
            Route::post('/preferences', [NotificationController::class, 'updatePreferences']);
        });

        // Lottery API (real‑time data)
        Route::prefix('lottery')->group(function () {
            Route::get('/jackpot', [ApiLotteryController::class, 'jackpot']);
            Route::get('/leaderboard', [ApiLotteryController::class, 'leaderboard']);
            Route::get('/achievements', [ApiLotteryController::class, 'achievements']);
            Route::get('/recent-wins', [ApiLotteryController::class, 'recentWins']);
            Route::get('/my-stats', [ApiLotteryController::class, 'myStats']);
        });
    });
});

// API v2 (enhanced)
Route::prefix('v2')->group(function () {
    Route::get('/health', function () {
        return response()->json(['version' => '2.0', 'status' => 'healthy']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/profile', function (\Illuminate\Http\Request $request) {
            $user = $request->user();
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'kyc_status' => $user->kyc_status,
                'is_verified' => $user->is_verified,
                'wallet_balance' => $user->wallet?->balance ?? 0,
            ]);
        });
        Route::get('/machines', [V2MachineController::class, 'index']);
        Route::get('/machines/{code}', [V2MachineController::class, 'show']);
        Route::post('/machines/{machine}/invest', [V2MachineController::class, 'invest']);
        Route::get('/portfolio/summary', [PortfolioController::class, 'summary']);
        Route::get('/wealth-tax/history', [WealthTaxController::class, 'history']);
    });
});

// API version info
Route::get('/v1/info', function () {
    return response()->json([
        'version' => '1.0.0',
        'name' => 'Racksephnox Crypto API',
        'description' => 'Divine Golden Spirit API',
        'endpoints' => [
            'auth' => '/v1/login, /v1/register, /v1/logout',
            'wallet' => '/v1/wallet/*',
            'machines' => '/v1/machines/*',
            'trading' => '/v1/trading/*',
            'kyc' => '/v1/kyc/*',
            'deposit' => '/v1/deposit/*',
            'withdrawal' => '/v1/withdrawal/*',
            'notifications' => '/v1/notifications/*',
            'lottery' => '/v1/lottery/*',
        ],
    ]);
});

// ==================== LOTTERY API v1 ====================
Route::prefix('v1/lottery')->group(function () {

    // Public
    Route::get('/games',           [App\Http\Controllers\Api\LotteryController::class, 'games']);
    Route::get('/jackpots',        [App\Http\Controllers\Api\LotteryController::class, 'jackpots']);
    Route::get('/leaderboard',     [App\Http\Controllers\Api\LotteryController::class, 'leaderboard']);
    Route::get('/recent-wins',     [App\Http\Controllers\Api\LotteryController::class, 'recentWins']);
    Route::get('/tournaments',     [App\Http\Controllers\Api\LotteryController::class, 'tournaments']);
    Route::post('/verify',         [App\Http\Controllers\Api\LotteryController::class, 'verify']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/my-stats',         [App\Http\Controllers\Api\LotteryController::class, 'myStats']);
        Route::post('/spin',            [App\Http\Controllers\Api\LotteryController::class, 'spin']);
        Route::post('/free-spin',       [App\Http\Controllers\Api\LotteryController::class, 'freeSpin']);
        Route::post('/buy-bonus',       [App\Http\Controllers\Api\LotteryController::class, 'buyBonus']);
        Route::get('/my-missions',      [App\Http\Controllers\Api\LotteryController::class, 'myMissions']);
        Route::post('/missions/{id}/claim', [App\Http\Controllers\Api\LotteryController::class, 'claimMission']);
        Route::get('/achievements',     [App\Http\Controllers\Api\LotteryController::class, 'achievements']);
        Route::get('/bonus-wheel',      [App\Http\Controllers\Api\LotteryController::class, 'bonusWheel']);
        Route::post('/bonus-wheel/spin',[App\Http\Controllers\Api\LotteryController::class, 'spinBonusWheel']);
        Route::get('/guilds',           [App\Http\Controllers\Api\LotteryController::class, 'guilds']);
        Route::get('/guilds/{tournament}/leaderboard', [App\Http\Controllers\Api\LotteryController::class, 'guildLeaderboard']);
    });
});
