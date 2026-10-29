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
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Core Balances (Safe)
        $walletBalance = (float) ($user->wallet?->balance ?? 0);
        
        $machineInvested = 0;
        $machineProfit = 0;
        $activeInvestments = 0;
        $investmentsGrouped = collect();

        if (Schema::hasTable('machine_investments')) {
            $machineInvested = (float) MachineInvestment::where('user_id', $user->id)->sum('amount');
            $machineProfit   = (float) MachineInvestment::where('user_id', $user->id)->sum('profit_credited');
            $activeInvestments = MachineInvestment::where('user_id', $user->id)->where('status', 'active')->count();
            
            $investmentsGrouped = MachineInvestment::where('user_id', $user->id)
                ->with('machine')
                ->get()
                ->groupBy(fn ($inv) => $inv->machine?->name ?? 'Archived');
        }

        $planInvested = 0;
        $planProfit = 0;
        if (Schema::hasTable('investments')) {
            $planInvested = (float) \App\Models\Investment::where('user_id', $user->id)->sum('amount');
            $planProfit   = (float) \App\Models\Investment::where('user_id', $user->id)->get()->sum(fn ($i) => $i->profit_credited);
            $activeInvestments += \App\Models\Investment::where('user_id', $user->id)->where('status', 'active')->count();
        }

        $totalInvested = $machineInvested + $planInvested;
        $totalProfit   = $machineProfit + $planProfit;
        $roi = $totalInvested > 0 ? round(($totalProfit / $totalInvested) * 100, 2) : 0;

        // 2. Banking (Safe)
        $totalDeposited = 0;
        $totalWithdrawn = 0;
        $recentTransactions = collect();
        
        if (Schema::hasTable('transactions')) {
            $totalDeposited = (float) Transaction::where('user_id', $user->id)->where('type', 'deposit')->sum('amount');
            $totalWithdrawn = abs((float) Transaction::where('user_id', $user->id)->where('type', 'withdrawal')->sum('amount'));
            $recentTransactions = Transaction::where('user_id', $user->id)->latest()->take(10)->get();
        }

        // 3. Chart Data (Safe)
        $profitLabels = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->format('M d'))->toArray();
        $profitRaw = $investmentsGrouped->flatten(1)
            ->filter(fn($inv) => $inv->created_at >= now()->subDays(30))
            ->groupBy(fn ($inv) => optional($inv->created_at)->format('Y-m-d') ?? 'Unknown')
            ->map(fn ($g) => $g->sum('profit_credited'))->values()->toArray();
        $profitData = array_pad($profitRaw, 30, 0);
        $profitHistory = ['labels' => $profitLabels, 'data' => $profitData];

        $portfolio = [
            'labels' => $investmentsGrouped->keys()->toArray(), 
            'data' => $investmentsGrouped->map(fn ($g) => $g->sum('amount'))->values()->toArray()
        ];
        if (empty($portfolio['labels'])) {
            $portfolio = ['labels' => ['No Active Machines'], 'data' => [1]];
        }

        // 4. Loans (Safe)
        $loans = collect();
        $loanStats = ['total_outstanding' => 0, 'active_count' => 0, 'pending_count' => 0];
        if (Schema::hasTable('loans')) {
            $loans = Loan::with('product')->where('user_id', $user->id)->latest()->get();
            $loanStats = [
                'total_borrowed'    => (float) $loans->sum('principal'),
                'total_outstanding' => (float) $loans->where('status', 'active')->sum('balance'),
                'active_count'      => $loans->where('status', 'active')->count(),
                'pending_count'     => $loans->where('status', 'pending')->count(),
            ];
        }

        // 5. Lottery Jackpot
        $lotteryJackpot = 1000;
        try {
            if (Schema::hasTable('lottery_games')) {
                $lotteryJackpot = LotteryGame::where('is_active', true)->first()?->progressive_jackpot ?? 1000;
            }
        } catch (\Throwable $e) {}

        // 6. Misc
        $btcPrice = Cache::remember('btc_price_kes', 60, fn () => rand(4_500_000, 5_500_000));
        $referralCount = $user->referrals()->count();
        $totalBonus = (float) Transaction::where('user_id', $user->id)->where('type', 'referral_bonus')->sum('amount');
        $unreadNotificationsCount = 0;
        try { if (Schema::hasTable('notifications')) $unreadNotificationsCount = $user->unreadNotifications->count(); } catch (\Throwable $e) {}

        return view('dashboard', compact(
            'user', 'walletBalance', 'totalInvested', 'totalProfit', 'activeInvestments',
            'totalDeposited', 'totalWithdrawn', 'totalInterest', 'recentTransactions',
            'profitHistory', 'portfolio', 'btcPrice', 'referralCount', 'totalBonus',
            'unreadNotificationsCount', 'roi', 'lotteryJackpot', 'loans', 'loanStats'
        ));
    }
}
