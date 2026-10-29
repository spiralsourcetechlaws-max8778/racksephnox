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
    public function index()
    {
        $user = Auth::user();

        // ─── 1. KPI Data (Defensive) ───
        $walletBalance = (float) ($user->wallet?->balance ?? 0);
        
        $machineInvested = 0;
        $machineProfit = 0;
        if (Schema::hasTable('machine_investments')) {
            $machineInvested = (float) MachineInvestment::where('user_id', $user->id)->sum('amount');
            // Check if column exists to prevent Postgres crash
            if (Schema::hasColumn('machine_investments', 'profit_credited')) {
                $machineProfit = (float) MachineInvestment::where('user_id', $user->id)->sum('profit_credited');
            } else {
                // Fallback to daily_profit if profit_credited is missing
                $machineProfit = (float) MachineInvestment::where('user_id', $user->id)->sum('daily_profit');
            }
        }

        $planInvested = 0;
        $planProfit = 0;
        if (Schema::hasTable('investments')) {
            $planInvested = (float) \App\Models\Investment::where('user_id', $user->id)->sum('amount');
            $planProfit   = (float) \App\Models\Investment::where('user_id', $user->id)->get()->sum(fn ($i) => $i->profit_credited ?? 0);
        }

        $totalInvested = $machineInvested + $planInvested;
        $totalProfit   = $machineProfit + $planProfit;
        $activeInvestments = Schema::hasTable('machine_investments') ? MachineInvestment::where('user_id', $user->id)->where('status', 'active')->count() : 0;
        $roi = $totalInvested > 0 ? round(($totalProfit / $totalInvested) * 100, 2) : 0;

        // ─── 2. Banking ───
        $totalDeposited = (float) Transaction::where('user_id', $user->id)->where('type', 'deposit')->sum('amount');
        $totalWithdrawn = abs((float) Transaction::where('user_id', $user->id)->where('type', 'withdrawal')->sum('amount'));
        
        // ─── 3. Recent Transactions ───
        $recentTransactions = Transaction::where('user_id', $user->id)->latest()->take(10)->get();

        // ─── 4. Profit History (30 days - Defensive) ───
        $profitLabels = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->format('M d'))->toArray();
        $profitData = array_fill(0, 30, 0); 
        if (Schema::hasTable('machine_investments')) {
            $profitRaw = MachineInvestment::where('user_id', $user->id)->where('created_at', '>=', now()->subDays(30))->get()
                ->groupBy(fn ($inv) => optional($inv->created_at)->format('Y-m-d') ?? 'Unknown')
                ->map(fn ($g) => $g->sum('daily_profit') ?? 0)->values()->toArray();
            $profitData = array_pad($profitRaw, 30, 0);
        }
        $profitHistory = ['labels' => $profitLabels, 'data' => $profitData];

        // ─── 5. Portfolio Breakdown (Defensive) ───
        $portfolioLabels = [];
        $portfolioData = [];
        if (Schema::hasTable('machine_investments') && Schema::hasTable('machines')) {
            $investmentsGrouped = MachineInvestment::where('user_id', $user->id)->with('machine')->get()
                ->groupBy(fn ($inv) => $inv->machine?->name ?? 'Archived');
            $portfolioLabels = $investmentsGrouped->keys()->toArray();
            $portfolioData = $investmentsGrouped->map(fn ($g) => $g->sum('amount'))->values()->toArray();
        }
        $portfolio = ['labels' => $portfolioLabels, 'data' => $portfolioData];

        // ─── 6. Misc ───
        $lotteryJackpot = 1000;
        if (Schema::hasTable('lottery_games')) {
            $lotteryJackpot = LotteryGame::where('is_active', true)->first()?->progressive_jackpot ?? 1000;
        }
        
        $referralCount = Schema::hasTable('users') ? $user->referrals()->count() : 0;
        $totalBonus = (float) Transaction::where('user_id', $user->id)->where('type', 'referral_bonus')->sum('amount');

        // ─── 7. Loans (Defensive) ───
        $loanStats = ['total_outstanding' => 0, 'active_count' => 0, 'pending_count' => 0];
        $loans = collect();
        if (Schema::hasTable('loans')) {
            $loans = Loan::with('product')->where('user_id', $user->id)->latest()->get();
            $loanStats = [
                'total_outstanding' => (float) $loans->where('status', 'active')->sum('balance'),
                'active_count'      => $loans->where('status', 'active')->count(),
                'pending_count'     => $loans->where('status', 'pending')->count(),
            ];
        }
        $upcomingRepayments = collect();

        // ─── 8. Credit Score ───
        $creditScore = null;
        try {
            $creditScore = Cache::remember("credit_score_{$user->id}", 300, fn () => app(LoanCreditService::class)->recalculate($user));
        } catch (\Throwable $e) {}

        return view('dashboard', compact(
            'user', 'walletBalance', 'totalInvested', 'totalProfit', 'activeInvestments',
            'totalDeposited', 'totalWithdrawn', 'recentTransactions', 'profitHistory',
            'portfolio', 'referralCount', 'totalBonus', 'roi', 'lotteryJackpot',
            'loans', 'loanStats', 'upcomingRepayments', 'creditScore'
        ));
    }
}
