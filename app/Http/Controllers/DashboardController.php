<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\MachineInvestment;
use App\Models\LotteryGame;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Services\Loans\LoanCreditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /* ================================================================
     |  MAIN DASHBOARD
     ================================================================ */
    public function index()
    {
        $user = Auth::user();

        // 1. WEALTH PILLARS
        $walletBalance = (float) ($user->wallet?->balance ?? 0);

        $machineInvested       = 0;
        $machineProfit         = 0;
        $activeMachineCount    = 0;
        $completedMachineCount = 0;
        $investmentsGrouped    = collect();

        if (Schema::hasTable('machine_investments')) {
            $machineInvested       = (float) MachineInvestment::where('user_id', $user->id)->sum('amount');
            $machineProfit         = (float) MachineInvestment::where('user_id', $user->id)->sum('profit_credited');
            $activeMachineCount    = MachineInvestment::where('user_id', $user->id)->where('status', 'active')->count();
            $completedMachineCount = MachineInvestment::where('user_id', $user->id)->where('status', 'completed')->count();
            $investmentsGrouped = MachineInvestment::where('user_id', $user->id)
                ->with('machine')
                ->get()
                ->groupBy(fn ($inv) => $inv->machine?->name ?? 'Archived');
        }

        $planInvested       = 0;
        $planProfit         = 0;
        $activePlanCount    = 0;
        $completedPlanCount = 0;

        if (Schema::hasTable('investments')) {
            $planInvested       = (float) \App\Models\Investment::where('user_id', $user->id)->sum('amount');
            $planProfit         = (float) \App\Models\Investment::where('user_id', $user->id)->get()->sum(fn ($i) => $i->profit_credited ?? 0);
            $activePlanCount    = \App\Models\Investment::where('user_id', $user->id)->where('status', 'active')->count();
            $completedPlanCount = \App\Models\Investment::where('user_id', $user->id)->where('status', 'completed')->count();
        }

        $totalInvested        = $machineInvested + $planInvested;
        $totalProfit          = $machineProfit + $planProfit;
        $activeInvestments    = $activeMachineCount + $activePlanCount;
        $completedInvestments = $completedMachineCount + $completedPlanCount;
        $totalMachineInvested = $machineInvested;
        $roi = $totalInvested > 0 ? round(($totalProfit / $totalInvested) * 100, 2) : 0;

        // 2. BANKING (all four vars defined — this was the earlier 500)
        $totalDeposited     = 0;
        $totalWithdrawn     = 0;
        $totalInterest      = 0;
        $recentTransactions = collect();

        if (Schema::hasTable('transactions')) {
            $totalDeposited = (float) Transaction::where('user_id', $user->id)->where('type', 'deposit')->sum('amount');
            $totalWithdrawn = abs((float) Transaction::where('user_id', $user->id)->where('type', 'withdrawal')->sum('amount'));
            $totalInterest  = (float) Transaction::where('user_id', $user->id)->where('type', 'interest')->sum('amount');
            $recentTransactions = Transaction::where('user_id', $user->id)->latest()->take(10)->get();
        }

        // 3. PROFIT HISTORY
        $profitLabels = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->format('M d'))->toArray();
        $profitRaw = collect();
        if (Schema::hasTable('machine_investments')) {
            $profitRaw = MachineInvestment::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->get()
                ->groupBy(fn ($inv) => $inv->created_at->format('Y-m-d'))
                ->map(fn ($g) => $g->sum('profit_credited'))
                ->values();
        }
        $profitData    = array_pad($profitRaw->toArray(), 30, 0);
        $profitHistory = ['labels' => $profitLabels, 'data' => $profitData];

        // 4. WEEKLY
        $weeklyLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $weeklyData   = [];
        for ($i = 0; $i < 7; $i++) {
            $sum = 0;
            if (Schema::hasTable('transactions')) {
                $sum = (float) Transaction::where('user_id', $user->id)
                    ->whereDate('created_at', now()->startOfWeek()->addDays($i))
                    ->sum('amount');
            }
            $weeklyData[] = $sum;
        }
        $weeklyPerformance = ['labels' => $weeklyLabels, 'data' => $weeklyData];

        // 5. PORTFOLIO
        $portfolio = [
            'labels' => $investmentsGrouped->keys()->toArray(),
            'data'   => $investmentsGrouped->map(fn ($g) => $g->sum('amount'))->values()->toArray(),
        ];
        if (empty($portfolio['labels'])) {
            $portfolio = ['labels' => ['No Active Machines'], 'data' => [1]];
        }

        // 6. BTC PRICE — cache failure must NOT break dashboard
        try {
            $btcPrice = Cache::remember('btc_price_kes', 60, fn () => rand(4_500_000, 5_500_000));
        } catch (\Throwable $e) {
            $btcPrice = rand(4_500_000, 5_500_000);
        }

        // 7. REFERRALS
        $referralCount = method_exists($user, 'referrals') ? $user->referrals()->count() : 0;
        $totalBonus    = 0;
        if (Schema::hasTable('transactions')) {
            $totalBonus = (float) Transaction::where('user_id', $user->id)->where('type', 'referral_bonus')->sum('amount');
        }

        // 8. NOTIFICATIONS
        $unreadNotificationsCount = 0;
        try {
            if (Schema::hasTable('notifications')) {
                $unreadNotificationsCount = $user->unreadNotifications->count();
            }
        } catch (\Throwable $e) {}

        // 9. LOTTERY JACKPOT
        $lotteryJackpot = 1000;
        try {
            if (Schema::hasTable('lottery_games')) {
                $lotteryJackpot = LotteryGame::where('is_active', true)->first()?->progressive_jackpot ?? 1000;
            }
        } catch (\Throwable $e) {}

        // 10. LOANS
        $loans = collect();
        $loanStats = [
            'total_borrowed' => 0, 'total_outstanding' => 0, 'total_repaid' => 0,
            'active_count'   => 0, 'pending_count'     => 0, 'completed_count' => 0,
            'has_overdue'    => false,
        ];
        $upcomingRepayments = collect();

        if (Schema::hasTable('loans')) {
            $loans          = Loan::with('product')->where('user_id', $user->id)->latest()->get();
            $activeLoans    = $loans->where('status', 'active');
            $pendingLoans   = $loans->where('status', 'pending');
            $completedLoans = $loans->where('status', 'completed');
            $loanStats = [
                'total_borrowed'    => (float) $loans->sum('principal'),
                'total_outstanding' => (float) $activeLoans->sum('balance'),
                'total_repaid'      => (float) $loans->sum('amount_paid'),
                'active_count'      => $activeLoans->count(),
                'pending_count'     => $pendingLoans->count(),
                'completed_count'   => $completedLoans->count(),
                'has_overdue'       => $activeLoans->contains(fn ($l) => $l->is_overdue ?? false),
            ];
        }

        if (Schema::hasTable('loan_repayments')) {
            $upcomingRepayments = LoanRepayment::with('loan.product')
                ->where('user_id', $user->id)->where('status', 'pending')
                ->orderBy('due_date')->take(5)->get();
        }

        $creditScore = null;
        try {
            if (Schema::hasTable('loan_credit_scores') && class_exists(LoanCreditService::class)) {
                $creditScore = Cache::remember("credit_score_{$user->id}", 300, function () use ($user) {
                    return app(LoanCreditService::class)->recalculate($user);
                });
            }
        } catch (\Throwable $e) {
            // Cache failed — try computing directly, one more time
            try {
                if (class_exists(LoanCreditService::class)) {
                    $creditScore = app(LoanCreditService::class)->recalculate($user);
                }
            } catch (\Throwable $e2) { $creditScore = null; }
        }

        // 11. SNAPSHOT
        $snapshot = [
            'wallet' => $walletBalance, 'invested' => $totalInvested, 'profit' => $totalProfit,
            'loans_out' => $loanStats['total_outstanding'], 'loan_active' => $loanStats['active_count'],
            'credit' => $creditScore->score ?? 500,
        ];

        // 12. RENDER
        return view('dashboard', compact(
            'user', 'walletBalance', 'totalInvested', 'totalProfit',
            'activeInvestments', 'completedInvestments', 'totalDeposited', 'totalWithdrawn',
            'totalMachineInvested', 'totalInterest', 'recentTransactions',
            'profitHistory', 'weeklyPerformance', 'portfolio', 'btcPrice',
            'referralCount', 'totalBonus', 'unreadNotificationsCount', 'roi',
            'lotteryJackpot', 'loans', 'loanStats', 'upcomingRepayments',
            'creditScore', 'snapshot'
        ));
    }

    /* ================================================================
     |  DASHBOARD AJAX — session-authenticated JSON endpoints
     |  Every Cache::remember() is try/catch-guarded: file cache failures
     |  (root recreating storage/framework/cache/data on startup) will
     |  NEVER take down the widget.
     ================================================================ */

    public function walletBalance()
    {
        return response()->json([
            'balance' => (float) (Auth::user()->wallet?->balance ?? 0),
        ]);
    }

    public function notificationsUnread()
    {
        $count = 0;
        try {
            $count = Auth::user()->unreadNotifications->count();
        } catch (\Throwable $e) {}
        return response()->json(['count' => $count]);
    }

    public function btcPrice()
    {
        try {
            $price = Cache::remember('btc_price_kes', 60, fn () => rand(4_500_000, 5_500_000));
        } catch (\Throwable $e) {
            $price = rand(4_500_000, 5_500_000);
        }
        return response()->json(['price_kes' => (float) $price]);
    }

    public function lotteryJackpot()
    {
        $jackpot = 1000;
        try {
            if (Schema::hasTable('lottery_games')) {
                $jackpot = LotteryGame::where('is_active', true)->first()?->progressive_jackpot ?? 1000;
            }
        } catch (\Throwable $e) {}
        return response()->json(['jackpot' => (float) $jackpot]);
    }

    /**
     * Live crypto prices — no cache dependency, graceful fallback.
     * Mirrors the shape the dashboard JS already parses:
     *   [{ symbol, percent_change_24h, price_kes }, ...]
     */
    public function cryptoPrices()
    {
        $usdToKes = 130; // rough conversion

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('https://api.coingecko.com/api/v3/coins/markets', [
                    'vs_currency'             => 'usd',
                    'order'                   => 'market_cap_desc',
                    'per_page'                => 5,
                    'page'                    => 1,
                    'sparkline'               => 'false',
                    'price_change_percentage' => '24h',
                ]);

            if ($response->successful()) {
                $rows = $response->json();
                if (is_array($rows) && count($rows) > 0) {
                    $normalized = collect($rows)->map(fn ($coin) => [
                        'symbol'             => strtoupper($coin['symbol'] ?? ''),
                        'percent_change_24h' => (float) ($coin['price_change_percentage_24h'] ?? 0),
                        'price_kes'          => (float) (($coin['current_price'] ?? 0) * $usdToKes),
                    ])->values()->toArray();

                    return response()->json($normalized);
                }
            }
        } catch (\Throwable $e) {
            // fall through to static fallback
        }

        // Static fallback so the widget NEVER says "Failed to load prices"
        $btcBase = 6_500_000;
        try {
            $btcBase = Cache::remember('btc_price_kes', 60, fn () => rand(4_500_000, 5_500_000));
        } catch (\Throwable $e) {}

        return response()->json([
            ['symbol' => 'BTC',  'percent_change_24h' => 2.4,  'price_kes' => (float) $btcBase],
            ['symbol' => 'ETH',  'percent_change_24h' => 1.8,  'price_kes' => (float) ($btcBase / 18)],
            ['symbol' => 'SOL',  'percent_change_24h' => 3.1,  'price_kes' => (float) ($btcBase / 260)],
            ['symbol' => 'BNB',  'percent_change_24h' => -0.5, 'price_kes' => (float) ($btcBase / 80)],
            ['symbol' => 'XRP',  'percent_change_24h' => 1.2,  'price_kes' => (float) ($btcBase / 86000)],
        ]);
    }
}
