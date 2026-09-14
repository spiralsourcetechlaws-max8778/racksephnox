<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\DepositRequest;
use App\Models\WithdrawalRequest;
use App\Models\MachineInvestment;
use App\Models\Machine;
use App\Models\LotterySpin;
use App\Models\LotteryGame;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /* ============================================================
         |  CORE STATS
         ============================================================ */
        $stats = [
            'total_users'                 => User::count(),
            'verified_users'              => User::where('is_verified', true)->count(),
            'total_invested'              => (float) Investment::sum('amount') + (float) MachineInvestment::sum('amount'),
            'total_machine_invested'      => (float) MachineInvestment::sum('amount'),
            'pending_deposits'            => DepositRequest::where('status', 'pending')->count(),
            'pending_withdrawals'         => WithdrawalRequest::where('status', 'pending')->count(),
            'pending_deposits_amount'     => (float) DepositRequest::where('status', 'pending')->sum('amount'),
            'pending_withdrawals_amount'  => (float) WithdrawalRequest::where('status', 'pending')->sum('amount'),
            'total_interest_paid'         => (float) Transaction::where('type', 'interest')->sum('amount'),
            'total_referral_bonus'        => (float) Transaction::where('type', 'referral_bonus')->sum('amount'),
            'total_machines'              => Machine::where('is_active', true)->count(),
            'rx0_active'                  => Machine::where('code', 'RX0')->where('is_active', true)->exists(),
            'new_users_today'             => User::whereDate('created_at', today())->count(),
        ];

        /* ============================================================
         |  LOANS DOMAIN STATS
         ============================================================ */
        $loanStats = [
            'total_products'         => LoanProduct::where('is_active', true)->count(),
            'pending_loans'          => Loan::where('status', 'pending')->count(),
            'pending_loans_amount'   => (float) Loan::where('status', 'pending')->sum('principal'),
            'approved_loans'         => Loan::where('status', 'approved')->count(),
            'active_loans'           => Loan::where('status', 'active')->count(),
            'total_disbursed'        => (float) Loan::whereIn('status', ['active','completed'])->sum('principal'),
            'total_outstanding'      => (float) Loan::where('status', 'active')->sum('balance'),
            'total_collected'        => (float) Loan::sum('amount_paid'),
            'defaulted_loans'        => Loan::where('status', 'defaulted')->count(),
            'completed_loans'        => Loan::where('status', 'completed')->count(),
            'overdue_repayments'     => LoanRepayment::where('status', 'pending')->where('due_date', '<', now())->count(),
            'overdue_amount'         => (float) LoanRepayment::where('status', 'pending')->where('due_date', '<', now())->sum('amount'),
        ];

        /* ============================================================
         |  USER GROWTH (30 days)
         ============================================================ */
        $userGrowthRaw = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $userGrowth = $this->fillDates($userGrowthRaw, 30, 'Y-m-d');

        /* ============================================================
         |  REVENUE TREND (30 days)
         ============================================================ */
        $revenueRaw = Transaction::selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->whereIn('type', ['deposit', 'machine_investment', 'investment'])
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $revenueData = $this->fillDates($revenueRaw, 30, 'Y-m-d');

        $revenueTrend = [
            'labels' => array_keys($revenueData),
            'data'   => array_values($revenueData),
        ];

        /* ============================================================
         |  LOTTERY ACTIVITY (30 days)
         ============================================================ */
        $lotteryRaw = LotterySpin::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(bet_amount) as bets')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $lotteryLabels = [];
        $lotterySpins  = [];
        $lotteryBets   = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $lotteryLabels[] = now()->subDays($i)->format('M d');
            $lotterySpins[]  = $lotteryRaw->get($date)->count ?? 0;
            $lotteryBets[]   = (float) ($lotteryRaw->get($date)->bets ?? 0);
        }
        $lotteryActivity = [
            'labels' => $lotteryLabels,
            'spins'  => $lotterySpins,
            'bets'   => $lotteryBets,
        ];

        /* ============================================================
         |  RECENT ACTIVITY FEED
         ============================================================ */
        $recentActivities = collect();

        // Recent deposits
        DepositRequest::with('user')->latest()->take(5)->get()->each(function ($d) use ($recentActivities) {
            $recentActivities->push([
                'created_at' => $d->created_at,
                'user'       => $d->user?->name ?? 'Unknown',
                'type'       => 'deposit',
                'amount'     => $d->amount,
                'status'     => $d->status,
            ]);
        });

        // Recent withdrawals
        WithdrawalRequest::with('user')->latest()->take(5)->get()->each(function ($w) use ($recentActivities) {
            $recentActivities->push([
                'created_at' => $w->created_at,
                'user'       => $w->user?->name ?? 'Unknown',
                'type'       => 'withdrawal',
                'amount'     => $w->amount,
                'status'     => $w->status,
            ]);
        });

        // Recent lottery spins
        LotterySpin::with('user')->latest()->take(5)->get()->each(function ($s) use ($recentActivities) {
            $recentActivities->push([
                'created_at' => $s->created_at,
                'user'       => $s->user?->name ?? 'Unknown',
                'type'       => 'lottery',
                'bet'        => $s->bet_amount ?? 0,
                'win'        => $s->win_amount ?? 0,
                'status'     => 'completed',
            ]);
        });

        // Recent loans
        Loan::with('user')->latest()->take(5)->get()->each(function ($l) use ($recentActivities) {
            $recentActivities->push([
                'created_at' => $l->created_at,
                'user'       => $l->user?->name ?? 'Unknown',
                'type'       => 'loan',
                'amount'     => $l->principal,
                'status'     => $l->status,
            ]);
        });

        $recentActivities = $recentActivities->sortByDesc('created_at')->take(15)->values()->toArray();

        /* ============================================================
         |  REVENUE TARGET (dummy/cached)
         ============================================================ */
        $revenueTarget = (object) [
            'current_revenue' => (float) Transaction::where('type', 'deposit')->sum('amount'),
            'target_amount'   => 1_000_000,
        ];
        $remaining      = max(0, $revenueTarget->target_amount - $revenueTarget->current_revenue);
        $targetProgress = $revenueTarget->target_amount > 0
            ? min(100, ($revenueTarget->current_revenue / $revenueTarget->target_amount) * 100)
            : 0;
        $daysLeft = 26;

        /* ============================================================
         |  RECENT LISTS
         ============================================================ */
        $recentUsers       = User::latest()->take(10)->get();
        $recentDeposits    = DepositRequest::with('user')->latest()->take(5)->get();
        $recentWithdrawals = WithdrawalRequest::with('user')->latest()->take(5)->get();
        $recentLoans       = Loan::with(['user', 'product'])->latest()->take(8)->get();

        /* ============================================================
         |  RENDER
         ============================================================ */
        return view('admin.dashboard', compact(
            'stats',
            'loanStats',
            'userGrowth',
            'revenueTrend',
            'lotteryActivity',
            'recentActivities',
            'recentUsers',
            'recentDeposits',
            'recentWithdrawals',
            'recentLoans',
            'revenueTarget',
            'daysLeft',
            'remaining',
            'targetProgress'
        ));
    }

    /* ---------- Live stats JSON ---------- */
    public function stats()
    {
        $stats = Cache::remember('admin_dashboard_stats', 60, function () {
            return [
                'total_users'         => User::count(),
                'new_users_today'     => User::whereDate('created_at', today())->count(),
                'active_investments'  => MachineInvestment::where('status', 'active')->count(),
                'total_pnl'           => (float) Transaction::where('type', 'interest')->sum('amount'),
                'daily_volume'        => (float) Transaction::whereDate('created_at', today())->sum('amount'),
                'machines_count'      => Machine::where('is_active', true)->count(),
                'pending_loans'       => Loan::where('status', 'pending')->count(),
                'active_loans'        => Loan::where('status', 'active')->count(),
                'total_loan_outstanding' => (float) Loan::where('status', 'active')->sum('balance'),
            ];
        });

        return response()->json($stats);
    }

    /* ---------- Helper: fill missing dates with zeros ---------- */
    protected function fillDates(array $data, int $days, string $format): array
    {
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $key = now()->subDays($i)->format($format);
            $out[$key] = (float) ($data[$key] ?? 0);
        }
        return $out;
    }
}
