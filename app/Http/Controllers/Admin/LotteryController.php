<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LotteryGame;
use App\Models\LotteryJackpotPool;
use App\Models\LotterySpin;
use App\Models\LotterySymbol;
use App\Models\LotteryPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotteryController extends Controller
{
    /* ============================================================
     | INDEX
     ============================================================ */
    public function index()
    {
        $games = LotteryGame::with('symbols')->orderBy('name')->get();

        $stats = [
            'total_games'    => $games->count(),
            'active_games'   => $games->where('is_active', true)->count(),
            'total_spins'    => LotterySpin::count(),
            'spins_today'    => LotterySpin::whereDate('created_at', today())->count(),
            'total_wagered'  => (float) LotterySpin::sum('bet_amount'),
            'total_payouts'  => (float) LotterySpin::sum('win_amount'),
        ];
        $stats['actual_rtp'] = $stats['total_wagered'] > 0
            ? round(($stats['total_payouts'] / $stats['total_wagered']) * 100, 2)
            : 0;

        return view('admin.lottery.index', compact('games', 'stats'));
    }

    /* ============================================================
     | ANALYTICS
     ============================================================ */
    public function analytics(Request $request)
    {
        $days = (int) ($request->days ?? 30);

        $byDay = LotterySpin::selectRaw('DATE(created_at) AS d, COUNT(*) AS spins, SUM(bet_amount) AS bets, SUM(win_amount) AS wins')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $labels = $byDay->pluck('d')->toArray();
        $spins  = $byDay->pluck('spins')->toArray();
        $bets   = $byDay->pluck('bets')->map(fn ($v) => (float) $v)->toArray();
        $wins   = $byDay->pluck('wins')->map(fn ($v) => (float) $v)->toArray();

        $rtp = 0;
        $totalBets = array_sum($bets);
        $totalWins = array_sum($wins);
        if ($totalBets > 0) $rtp = round(($totalWins / $totalBets) * 100, 2);

        return view('admin.lottery.analytics', compact('labels', 'spins', 'bets', 'wins', 'rtp', 'days'));
    }

    /* ============================================================
     | EDIT GAME
     ============================================================ */
    public function edit(LotteryGame $lottery)
    {
        $symbols = LotterySymbol::orderBy('name')->get();
        $payouts = LotteryPayout::where('lottery_game_id', $lottery->id)
            ->with('symbol')
            ->get();
        return view('admin.lottery.edit', compact('lottery', 'symbols', 'payouts'));
    }

    public function update(Request $request, LotteryGame $lottery)
    {
        $data = $request->validate([
            'name'                     => 'required|string|max:255',
            'description'              => 'nullable|string',
            'min_bet'                  => 'required|numeric|min:0',
            'max_bet'                  => 'required|numeric|min:0',
            'ticket_price'             => 'required|numeric|min:0',
            'progressive_jackpot'      => 'nullable|numeric|min:0',
            'jackpot_contribution_rate'=> 'nullable|numeric|min:0|max:100',
            'base_rtp'                 => 'nullable|numeric|min:0|max:100',
            'vip_rtp'                  => 'nullable|numeric|min:0|max:100',
            'promo_rtp'                => 'nullable|numeric|min:0|max:100',
            'volatility'               => 'nullable|in:low,medium,high,extreme',
            'enable_free_spins'        => 'boolean',
            'enable_bonus_buy'         => 'boolean',
            'bonus_buy_price'          => 'nullable|numeric|min:0',
            'is_active'                => 'boolean',
        ]);

        $data['is_active']          = $request->boolean('is_active');
        $data['enable_free_spins']  = $request->boolean('enable_free_spins');
        $data['enable_bonus_buy']   = $request->boolean('enable_bonus_buy');

        $lottery->update($data);

        return back()->with('success', 'Game updated successfully.');
    }

    /* ============================================================
     | SYMBOLS
     ============================================================ */
    public function symbols()
    {
        $symbols = LotterySymbol::orderBy('name')->get();
        return view('admin.lottery.symbols', compact('symbols'));
    }

    public function storeSymbol(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:50|unique:lottery_symbols,name',
            'display_name' => 'nullable|string|max:100',
            'icon'         => 'nullable|string|max:100',
            'multiplier'   => 'required|numeric|min:0',
            'is_divine'    => 'boolean',
        ]);
        $data['is_divine'] = $request->boolean('is_divine');

        LotterySymbol::create($data);
        return back()->with('success', 'Symbol added.');
    }

    public function updateSymbol(Request $request, LotterySymbol $symbol)
    {
        $data = $request->validate([
            'display_name' => 'nullable|string|max:100',
            'icon'         => 'nullable|string|max:100',
            'multiplier'   => 'required|numeric|min:0',
            'is_divine'    => 'boolean',
        ]);
        $data['is_divine'] = $request->boolean('is_divine');

        $symbol->update($data);
        return back()->with('success', 'Symbol updated.');
    }

    public function destroySymbol(LotterySymbol $symbol)
    {
        $symbol->delete();
        return back()->with('success', 'Symbol removed.');
    }

    /* ============================================================
     | RTP SIMULATOR
     ============================================================ */
    public function rtpSimulate(Request $request)
    {
        $data = $request->validate([
            'game_id' => 'required|exists:lottery_games,id',
            'spins'   => 'required|integer|min:100|max:100000',
        ]);

        $game = LotteryGame::findOrFail($data['game_id']);
        $spins = (int) $data['spins'];
        $bet = (float) $game->ticket_price;

        $rng = app(\App\Services\Lottery\RngService::class);
        $service = app(\App\Services\Lottery\LotteryService::class);

        $totalBet = 0;
        $totalWin = 0;
        $hits = 0;
        $bigWins = 0;

        for ($i = 0; $i < $spins; $i++) {
            $result = $rng->spin(auth()->user(), $game);
            $evaluation = $service->evaluate($game, $result['main_line']);
            $win = $bet * $evaluation['multiplier'];

            $totalBet += $bet;
            $totalWin += $win;
            if ($win > 0) $hits++;
            if ($win >= 1000) $bigWins++;
        }

        $rtp = $totalBet > 0 ? round(($totalWin / $totalBet) * 100, 2) : 0;

        return response()->json([
            'spins'      => $spins,
            'total_bet'  => $totalBet,
            'total_win'  => $totalWin,
            'actual_rtp' => $rtp,
            'hit_rate'   => round(($hits / $spins) * 100, 2),
            'big_wins'   => $bigWins,
            'theoretical_rtp' => $game->base_rtp,
        ]);
    }

    /* ============================================================
     | JACKPOT POOLS
     ============================================================ */
    public function jackpots()
    {
        $pools = LotteryJackpotPool::orderByRaw(
            "CASE tier WHEN 'bronze' THEN 1 WHEN 'silver' THEN 2 WHEN 'gold' THEN 3 WHEN 'cosmic' THEN 4 END"
        )->get();

        $recent = \App\Models\LotteryJackpotWin::with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.lottery.jackpots', compact('pools', 'recent'));
    }

    public function updateJackpot(Request $request, LotteryJackpotPool $pool)
    {
        $data = $request->validate([
            'seed_amount'        => 'required|numeric|min:0',
            'ceiling_amount'     => 'nullable|numeric|min:0',
            'contribution_rate'  => 'required|numeric|min:0|max:100',
            'is_active'          => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $pool->update($data);

        return back()->with('success', 'Pool updated.');
    }

    public function forceJackpotDrop(LotteryJackpotPool $pool)
    {
        $pool->update(['must_drop' => true]);
        return back()->with('success', 'Next eligible spin will win the pool.');
    }

    public function resetJackpot(LotteryJackpotPool $pool)
    {
        $pool->update([
            'current_pool' => $pool->seed_amount,
            'must_drop'    => false,
        ]);
        return back()->with('success', 'Pool reset to seed.');
    }

    /* ============================================================
     | EXPORT
     ============================================================ */
    public function export()
    {
        $spins = LotterySpin::with('user', 'game')
            ->latest()
            ->limit(10000)
            ->get();

        $filename = 'lottery-spins-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($spins) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['ID', 'Date', 'User', 'Game', 'Bet', 'Win', 'Net', 'Jackpot', 'Tier']);
            foreach ($spins as $s) {
                fputcsv($fh, [
                    $s->id,
                    $s->created_at,
                    $s->user?->name ?? '—',
                    $s->game?->name ?? '—',
                    $s->bet_amount,
                    $s->win_amount,
                    $s->net_result,
                    $s->jackpot_won,
                    $s->jackpot_tier,
                ]);
            }
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ============================================================
     | IMPORT SYMBOLS
     ============================================================ */
    public function importSymbols(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,json,txt',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $rows = json_decode($content, true);

        if (!is_array($rows)) {
            // Try CSV
            $rows = array_map('str_getcsv', explode("\n", trim($content)));
            $header = array_shift($rows);
            $rows = array_map(fn ($r) => array_combine($header, $r), $rows);
        }

        $imported = 0;
        foreach ($rows as $row) {
            if (empty($row['name'])) continue;
            LotterySymbol::updateOrCreate(
                ['name' => $row['name']],
                [
                    'display_name' => $row['display_name'] ?? null,
                    'icon'         => $row['icon'] ?? null,
                    'multiplier'   => $row['multiplier'] ?? 1,
                    'is_divine'    => !empty($row['is_divine']),
                ]
            );
            $imported++;
        }

        return back()->with('success', "Imported {$imported} symbols.");
    }
}
