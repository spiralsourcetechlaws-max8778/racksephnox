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
    
    // Secured M-Pesa Callback
    Route::post('/mpesa/callback', [MpesaController::class, 'callback'])
        ->middleware('mpesa.source')
        ->name('api.mpesa.callback');
        
    Route::get('/crypto/prices', [CryptoController::class, 'prices']);
    Route::get('/crypto/history', [CryptoController::class, 'history']);
    Route::get('/crypto/market', [CryptoController::class, 'marketData']);
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
            Route::get('/candles/{interval}', [TradingController::class, 'candles']);
            Route::get('/order-book', [TradingController::class, 'orderBook']);
            Route::post('/transfer/in', [TradingController::class, 'transferToTrading']);
            Route::post('/transfer/out', [TradingController::class, 'transferFromTrading']);
        });

        // Machines API
        Route::prefix('machines')->group(function () {
            Route::get('/', [MachineController::class, 'index']);
            Route::get('/my-investments', [MachineController::class, 'myInvestments']);
            Route::get('/{code}', [MachineController::class, 'show']);
            Route::post('/{machine}/invest', [MachineController::class, 'invest']);
            Route::get('/{investment}/status', [MachineController::class, 'status']);
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
        
        // Note: Lottery authenticated routes are handled in the dedicated block below
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
    Route::get('/games',           [ApiLotteryController::class, 'games']);
    Route::get('/jackpots',        [ApiLotteryController::class, 'jackpots']);
    Route::get('/leaderboard',     [ApiLotteryController::class, 'leaderboard']);
    Route::get('/recent-wins',     [ApiLotteryController::class, 'recentWins']);
    Route::get('/tournaments',     [ApiLotteryController::class, 'tournaments']);
    Route::post('/verify',         [ApiLotteryController::class, 'verify']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/my-stats',         [ApiLotteryController::class, 'myStats']);
        Route::post('/spin',            [ApiLotteryController::class, 'spin']);
        Route::post('/free-spin',       [ApiLotteryController::class, 'freeSpin']);
        Route::post('/buy-bonus',       [ApiLotteryController::class, 'buyBonus']);
        Route::get('/my-missions',      [ApiLotteryController::class, 'myMissions']);
        Route::post('/missions/{id}/claim', [ApiLotteryController::class, 'claimMission']);
        Route::get('/achievements',     [ApiLotteryController::class, 'achievements']);
        Route::get('/bonus-wheel',      [ApiLotteryController::class, 'bonusWheel']);
        Route::post('/bonus-wheel/spin',[ApiLotteryController::class, 'spinBonusWheel']);
        Route::get('/guilds',           [ApiLotteryController::class, 'guilds']);
        Route::get('/guilds/{tournament}/leaderboard', [ApiLotteryController::class, 'guildLeaderboard']);
    });
});
