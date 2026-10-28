<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MachineInvestment;
use App\Models\Transaction;
use App\Models\TradeOrder;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

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

        $monthlyInvestments = MachineInvestment::select(
                DB::raw("strftime('%Y-%m', created_at) as month"),
                DB::raw("SUM(amount) as total")
            )->groupBy('month')->orderBy('month')->get();

        $monthlyUsers = User::select(
                DB::raw("strftime('%Y-%m', created_at) as month"),
                DB::raw("COUNT(*) as total")
            )->groupBy('month')->orderBy('month')->get();

        $monthlyTradingVolume = TradeOrder::where('status', 'completed')
            ->select(
                DB::raw("strftime('%Y-%m', created_at) as month"),
                DB::raw("SUM(filled_kes) as total")
            )->groupBy('month')->orderBy('month')->get();

        return view('admin.reports.index', compact(
            'totalUsers', 'verifiedUsers', 'totalInvested', 'totalTradingVolume',
            'totalDeposited', 'totalWithdrawn', 'totalMachineInvested',
            'monthlyInvestments', 'monthlyUsers', 'monthlyTradingVolume'
        ));
    }

    public function exportUsers()
    {
        $users = User::all(['id', 'name', 'email', 'phone', 'created_at']);
        return $this->csvResponse($users, 'users.csv');
    }

    public function exportTransactions()
    {
        $transactions = Transaction::with('user')->get(['id', 'user_id', 'type', 'amount', 'status', 'description', 'created_at']);
        return $this->csvResponse($transactions, 'transactions.csv');
    }

    public function exportInvestments()
    {
        $investments = MachineInvestment::with('user', 'machine')->get();
        return $this->csvResponse($investments, 'investments.csv');
    }

    public function exportTrading()
    {
        $trades = TradeOrder::with('user')->where('status', 'completed')->get();
        return $this->csvResponse($trades, 'trades.csv');
    }

    public function exportPdfReport()
    {
        // Simple summary JSON (PDF can be added later with DomPDF)
        $data = [
            'total_users' => User::count(),
            'total_invested' => MachineInvestment::sum('amount'),
            'total_trading_volume' => TradeOrder::sum('filled_kes'),
            'report_date' => now()->toDateTimeString(),
        ];
        return response()->json($data);
    }

    protected function csvResponse($data, $filename)
    {
        $csv = Writer::createFromString('');
        if ($data->isNotEmpty()) {
            $csv->insertOne(array_keys($data->first()->getAttributes()));
            foreach ($data as $row) {
                $csv->insertOne($row->toArray());
            }
        }
        return response($csv->toString(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Dispatcher for all export types — driven by POST /admin/reports/export.
     *
     * The /admin/reports page has one "Export" button with a dropdown
     * selecting `type` (users / transactions / investments / trading / pdf).
     * This method routes to the correct exporter based on that value.
     *
     * Falls back to `users` if type is missing, so the exporter still
     * works if the form hasn't been updated to include the dropdown.
     */
    public function export(\Illuminate\Http\Request $request)
    {
        $type = (string) $request->input('type', 'users');

        return match ($type) {
            'users'        => $this->exportUsers(),
            'transactions' => $this->exportTransactions(),
            'investments'  => $this->exportInvestments(),
            'trading'      => $this->exportTrading(),
            'pdf'          => $this->exportPdfReport(),
            default        => back()->withErrors(['error' => "Unknown export type: {$type}"]),
        };
    }

}
