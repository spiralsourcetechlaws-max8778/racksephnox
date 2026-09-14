<?php

use App\Http\Controllers\LotteryController;
use App\Http\Controllers\Lottery\TournamentController;
use App\Http\Controllers\Lottery\GuildController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('lottery')->name('lottery.')->group(function () {

    /* Lobby */
    Route::get('/', [LotteryController::class, 'index'])->name('index');
    Route::get('/dashboard', [LotteryController::class, 'dashboard'])->name('dashboard');

    /* Gameplay */
    Route::post('/spin',      [LotteryController::class, 'spin'])->name('spin');
    Route::post('/free-spin', [LotteryController::class, 'freeSpin'])->name('free-spin');
    Route::post('/buy-bonus', [LotteryController::class, 'buyBonus'])->name('buy-bonus');
    Route::post('/verify',    [LotteryController::class, 'verify'])->name('verify');

    /* Progression */
    Route::get('/history',       [LotteryController::class, 'history'])->name('history');
    Route::get('/leaderboard/{period?}', [LotteryController::class, 'leaderboard'])->name('leaderboard');
    Route::get('/achievements',  [LotteryController::class, 'achievements'])->name('achievements');
    Route::get('/missions',      [LotteryController::class, 'missions'])->name('missions');
    Route::post('/missions/{userMission}/claim', [LotteryController::class, 'claimMission'])->name('missions.claim');

    /* Bonus wheel */
    Route::get('/bonus-wheel',      [LotteryController::class, 'bonusWheel'])->name('bonus-wheel');
    Route::post('/bonus-wheel/spin',[LotteryController::class, 'spinBonusWheel'])->name('bonus-wheel.spin');

    /* Tournaments */
    Route::get('/tournaments',              [TournamentController::class, 'index'])->name('tournaments');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');

    /* Guilds */
    Route::get('/guilds',                  [GuildController::class, 'index'])->name('guilds');
    Route::post('/guilds',                 [GuildController::class, 'create'])->name('guilds.create');
    Route::get('/guilds/{guild}',          [GuildController::class, 'show'])->name('guilds.show');
    Route::post('/guilds/{guild}/join',    [GuildController::class, 'join'])->name('guilds.join');
    Route::post('/guilds/leave',           [GuildController::class, 'leave'])->name('guilds.leave');

    /* Legacy alias */
    Route::get('/social', [GuildController::class, 'index'])->name('social');
});

// ==================== RESPONSIBLE GAMING ====================
Route::middleware(['auth', 'verified'])->prefix('lottery')->name('lottery.')->group(function () {
    Route::get('/responsible',                    [App\Http\Controllers\Lottery\ResponsibleGamingController::class, 'index'])->name('responsible');
    Route::post('/responsible/update',            [App\Http\Controllers\Lottery\ResponsibleGamingController::class, 'update'])->name('responsible.update');
    Route::post('/responsible/cool-down',         [App\Http\Controllers\Lottery\ResponsibleGamingController::class, 'coolDown'])->name('responsible.cool-down');
    Route::post('/responsible/self-exclude',      [App\Http\Controllers\Lottery\ResponsibleGamingController::class, 'selfExclude'])->name('responsible.self-exclude');

    Route::get('/fair',                           [App\Http\Controllers\Lottery\LotteryFairController::class, 'index'])->name('fair');
    Route::post('/fair/rotate',                   [App\Http\Controllers\Lottery\LotteryFairController::class, 'rotate'])->name('fair.rotate');
    Route::post('/fair/client-seed',              [App\Http\Controllers\Lottery\LotteryFairController::class, 'setClientSeed'])->name('fair.client-seed');

    Route::get('/jackpots',                       fn () => view('lottery.jackpots', [
        'jackpots' => app(\App\Services\Lottery\JackpotPoolService::class)->allPools(),
        'recentWins' => \Illuminate\Support\Facades\Cache::get('lottery_recent_wins', []),
    ]))->name('jackpots');
});
