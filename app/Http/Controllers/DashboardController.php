<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\MachineInvestment;
use App\Models\LotteryGame;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanCreditScore;
use App\Services\Loans\LoanCreditService;
use App\Services\Investment\UnifiedInvestmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        protected UnifiedInvestmentService $investments
    ) {}

    public function index()
    {
        $user = Auth::user();

        /* ============================================================
         |  WEALTH PILLARS
         ============================================================ */
        $walletBalance = (float) ($user->wallet?->balance ?? 0);

        $machineInvested = (float) MachineInvestment::where('user_id', $user->id)->sum('amount');
        $machineProfit   = (float) MachineInvestment::where('user_id', $user->id)->sum('profit_credited');

        $planInvested = (float) \App\Models\Investment::where('user_id', $user->id)->sum('amount');
        $planProfit   = (float) \App\Models\Investment::where('user_id', $user->id)
                            ->get()
                            ->sum(fn ($i) => $i->profit_credited);

        $totalInvested = $machineInvested + $planInvested;
        $totalProfit   = $machineProfit + $planProfit;

        $activeInvestments = MachineInvestment::where('user_id', $user->id)->where('status', 'active')->count()
                           + \App\Models\Investment::where('user_id', $user->id)->where('status', 'active')->count();

        $completedInvestments = MachineInvestment::where('user_id', $user->id)->where('status', 'completed')->count()
                              + \App\Models\Investment::where('user_id', $user->id)->where('status', 'completed')->count();

        /* ============================================================
         |  BANKING
         ============================================================ */
        $totalDeposited = (float) Transaction::where('user_id', $user->id)->where('type', 'deposit')->sum('amount');
        $totalWithdrawn = abs((float) Transaction::where('user_id', $user->id)->where('type', 'withdrawal')->sum('amount'));
        $totalInterest  = (float) Transaction::where('user_id', $user->id)->where('type', 'interest')->sum('amount');
        $totalMachineInvested = $machineInvested;

        /* ============================================================
         |  RECENT TRANSACTIONS
         ============================================================ */
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        /* ============================================================
         |  PROFIT HISTORY (30 days)
         ============================================================ */
        $profitLabels = collect(range(29, 0))
            ->map(fn ($i) => now()->subDays($i)->format('M d'))
            ->toArray();

        $profitData = MachineInvestment::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->groupBy(fn ($inv) => $inv->created_at->format('Y-m-d'))
            ->map(fn ($g) => $g->sum('profit_credited'))
            ->values()
            ->toArray();

        $profitData    = array_pad($profitData, 30, 0);
        $profitHistory = ['labels' => $profitLabels, 'data' => $profitData];

        /* ============================================================
         |  WEEKLY PERFORMANCE
         ============================================================ */
        $weeklyLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $weeklyData   = [];
        for ($i = 0; $i < 7; $i++) {
            $weeklyData[] = (float) Transaction::where('user_id', $user->id)
                ->whereDate('created_at', now()->startOfWeek()->addDays($i))
                ->sum('amount');
        }
        $weeklyPerformance = ['labels' => $weeklyLabels, 'data' => $weeklyData];

        /* ============================================================
         |  PORTFOLIO BREAKDOWN
         ============================================================ */
        $investmentsGrouped = MachineInvestment::where('user_id', $user->id)
            ->with('machine')
            ->get()
            ->groupBy(fn ($inv) => $inv->machine?->name ?? 'Archived');

        $portfolio = [
            'labels' => $investmentsGrouped->keys()->toArray(),
            'data'   => $investmentsGrouped->map(fn ($g) => $g->sum('amount'))->values()->toArray(),
        ];

        /* ============================================================
         |  BTC PRICE (cached)
         ============================================================ */
        $btcPrice = Cache::remember('btc_price_kes', 60, fn () => rand(4_500_000, 5_500_000));

        /* ============================================================
         |  REFERRALS
         ============================================================ */
        $referralCount = $user->referrals()->count();
        $totalBonus    = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'referral_bonus')
            ->sum('amount');

        /* ============================================================
         |  NOTIFICATIONS
         ============================================================ */
        $unreadNotificationsCount = $user->unreadNotifications->count();

        /* ============================================================
         |  ROI
         ============================================================ */
        $roi = $totalInvested > 0 ? round(($totalProfit / $totalInvested) * 100, 2) : 0;

        /* ============================================================
         |  LOTTERY JACKPOT
         ============================================================ */
        $lotteryJackpot = LotteryGame::where('is_active', true)->first()?->progressive_jackpot ?? 1000;

        /* ============================================================
         |  LOANS DOMAIN
         ============================================================ */
        $loans = Loan::with('product')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

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
            'has_overdue'       => $activeLoans->contains(fn ($l) => $l->is_overdue),
        ];

        $upcomingRepayments = LoanRepayment::with('loan.product')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->take(5)
            ->get();

        $creditScore = Cache::remember("credit_score_{$user->id}", 300, function () use ($user) {
            return app(LoanCreditService::class)->recalculate($user);
        });

        /* ============================================================
         |  QUICK STATS SNAPSHOT
         ============================================================ */
        $snapshot = [
            'wallet'        => $walletBalance,
            'invested'      => $totalInvested,
            'profit'        => $totalProfit,
            'loans_out'     => $loanStats['total_outstanding'],
            'loan_active'   => $loanStats['active_count'],
            'credit'        => $creditScore->score ?? 500,
        ];

        /* ============================================================
         |  RENDER
         ============================================================ */
        return view('dashboard', compact(
            'user',
            'walletBalance',
            'totalInvested',
            'totalProfit',
            'activeInvestments',
            'completedInvestments',
            'totalDeposited',
            'totalWithdrawn',
            'totalMachineInvested',
            'totalInterest',
            'recentTransactions',
            'profitHistory',
            'weeklyPerformance',
            'portfolio',
            'btcPrice',
            'referralCount',
            'totalBonus',
            'unreadNotificationsCount',
            'roi',
            'lotteryJackpot',
            'loans',
            'loanStats',
            'upcomingRepayments',
            'creditScore',
            'snapshot'
        ));
    }
}
