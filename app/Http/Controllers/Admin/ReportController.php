<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MachineInvestment;
use App\Models\Transaction;
use App\Models\TradeOrder;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $verifiedUsers = User::where('is_verified', true)->count();
        $totalInvested = MachineInvestment::sum('amount');
        $totalTradingVolume = TradeOrder::where('status', 'completed')->sum('filled_kes');
        $totalDeposited = Transaction::where('type', 'deposit')->sum('amount');
        $totalWithdrawn = abs(Transaction::where('type', 'withdrawal')->sum('amount'));
        $totalMachineInvested = MachineInvestment::sum('amount');

        // PHP grouping for cross-DB compatibility
        $monthlyInvestments = MachineInvestment::all()
            ->groupBy(fn($i) => optional($i->created_at)->format('Y-m') ?? 'Unknown')
            ->map(fn($g, $m) => (object)['month' => $m, 'total' => $g->sum('amount')])->values();

        $monthlyUsers = User::all()
            ->groupBy(fn($u) => optional($u->created_at)->format('Y-m') ?? 'Unknown')
            ->map(fn($g, $m) => (object)['month' => $m, 'total' => $g->count()])->values();

        $monthlyTradingVolume = TradeOrder::where('status', 'completed')->get()
            ->groupBy(fn($t) => optional($t->created_at)->format('Y-m') ?? 'Unknown')
            ->map(fn($g, $m) => (object)['month' => $m, 'total' => $g->sum('filled_kes')])->values();

        return view('admin.reports.index', compact(
            'totalUsers', 'verifiedUsers', 'totalInvested', 'totalTradingVolume',
            'totalDeposited', 'totalWithdrawn', 'totalMachineInvested',
            'monthlyInvestments', 'monthlyUsers', 'monthlyTradingVolume'
        ));
    }

    public function export(Request $request) { return back(); }
}
