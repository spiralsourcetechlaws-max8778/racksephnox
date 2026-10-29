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
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(
        protected UnifiedInvestmentService $investments
    ) {}

    public function index()
    {
        $user = Auth::user();

        // 1. KPI Data
        $walletBalance = (float) ($user->wallet?->balance ?? 0);
        $machineInvested = (float) MachineInvestment::where('user_id', $user->id)->sum('amount');
        $machineProfit   = (float) MachineInvestment::where('user_id', $user->id)->sum('profit_credited');
        $planInvested = (float) \App\Models\Investment::where('user_id', $user->id)->sum('amount');
        $planProfit   = (float) \App\Models\Investment::where('user_id', $user->id)->get()->sum(fn ($i) => $i->profit_credited);

        $totalInvested = $machineInvested + $planInvested;
        $totalProfit   = $machineProfit + $planProfit;
        $activeInvestments = MachineInvestment::where('user_id', $user->id)->where('status', 'active')->count() + \App\Models\Investment::where('user_id', $user->id)->where('status', 'active')->count();
        $completedInvestments = MachineInvestment::where('user_id', $user->id)->where('status', 'completed')->count() + \App\Models\Investment::where('user_id', $user->id)->where('status', 'completed')->count();
        $roi = $totalInvested > 0 ? round(($totalProfit / $totalInvested) * 100, 2) : 0;

        // 2. Banking
        $totalDeposited = (float) Transaction::where('user_id', $user->id)->where('type', 'deposit')->sum('amount');
        $totalWithdrawn = abs((float) Transaction::where('user_id', $user->id)->where('type', 'withdrawal')->sum('amount'));
        $totalInterest  = (float) Transaction::where('user_id', $user->id)->where('type', 'interest')->sum('amount');

        // 3. Recent Transactions
        $recentTransactions = Transaction::where('user_id', $user->id)->latest()->take(10)->get();

        // 4. Profit History (30 days)
        $profitLabels = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->format('M d'))->toArray();
        $profitRaw = MachineInvestment::where('user_id', $user->id)->where('created_at', '>=', now()->subDays(30))->get()
            ->groupBy(fn ($inv) => optional($inv->created_at)->format('Y-m-d') ?? 'Unknown')
            ->map(fn ($g) => $g->sum('profit_credited'))->values()->toArray();
        $profitData = array_pad($profitRaw, 30, 0);
        $profitHistory = ['labels' => $profitLabels, 'data' => $profitData];

        // 5. Weekly Performance
        $weeklyLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $weeklyData = [];
        for ($i = 0; $i < 7; $i++) {
            $weeklyData[] = (float) Transaction::where('user_id', $user->id)->whereDate('created_at', now()->startOfWeek()->addDays($i))->sum('amount');
        }
        $weeklyPerformance = ['labels' => $weeklyLabels, 'data' => $weeklyData];

        // 6. Portfolio Breakdown
        $investmentsGrouped = MachineInvestment::where('user_id', $user->id)->with('machine')->get()->groupBy(fn ($inv) => $inv->machine?->name ?? 'Archived');
        $portfolio = ['labels' => $investmentsGrouped->keys()->toArray(), 'data' => $investmentsGrouped->map(fn ($g) => $g->sum('amount'))->values()->toArray()];

        // 7. Misc
        $btcPrice = Cache::remember('btc_price_kes', 60, fn () => rand(4_500_000, 5_500_000));
        $referralCount = $user->referrals()->count();
        $totalBonus = (float) Transaction::where('user_id', $user->id)->where('type', 'referral_bonus')->sum('amount');
        
        $unreadNotificationsCount = 0;
        try { if (Schema::hasTable('notifications')) $unreadNotificationsCount = $user->unreadNotifications->count(); } catch (\Throwable $e) {}

        $lotteryJackpot = LotteryGame::where('is_active', true)->first()?->progressive_jackpot ?? 1000;

        // 8. Loans
        $loans = Loan::with('product')->where('user_id', $user->id)->latest()->get();
        $loanStats = [
            'total_borrowed'    => (float) $loans->sum('principal'),
            'total_outstanding' => (float) $loans->where('status', 'active')->sum('balance'),
            'total_repaid'      => (float) $loans->sum('amount_paid'),
            'active_count'      => $loans->where('status', 'active')->count(),
            'pending_count'     => $loans->where('status', 'pending')->count(),
        ];
        $upcomingRepayments = LoanRepayment::with('loan.product')->where('user_id', $user->id)->where('status', 'pending')->orderBy('due_date')->take(5)->get();

        $creditScore = null;
        try {
            $creditScore = Cache::remember("credit_score_{$user->id}", 300, fn () => app(LoanCreditService::class)->recalculate($user));
        } catch (\Throwable $e) {}

        return view('dashboard', compact(
            'user', 'walletBalance', 'totalInvested', 'totalProfit', 'activeInvestments', 'completedInvestments',
            'totalDeposited', 'totalWithdrawn', 'totalMachineInvested', 'totalInterest', 'recentTransactions',
            'profitHistory', 'weeklyPerformance', 'portfolio', 'btcPrice', 'referralCount', 'totalBonus',
            'unreadNotificationsCount', 'roi', 'lotteryJackpot', 'loans', 'loanStats', 'upcomingRepayments', 'creditScore'
        ));
    }
}
