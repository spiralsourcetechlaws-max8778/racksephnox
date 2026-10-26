@extends('admin.layouts.app')

@section('page-title', 'Reports')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-gold hover:text-yellow-400">Dashboard</a>
    <span class="mx-1">/</span> Reports
@endsection

@section('content')
<div class="space-y-4">

    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-white">Financial Reports</h2>
            <p class="text-sm text-gray-400">Export platform-wide financial data for accounting.</p>
        </div>
        <form method="POST" action="{{ route('admin.reports.export') }}" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded text-sm">
                <i class="fas fa-download mr-1"></i> Export All Data
            </button>
        </form>
    </div>

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif

    {{-- Report categories --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        <div class="admin-card p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-arrow-down text-green-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Deposits</h3>
                    <p class="text-xs text-gray-500">Approved inflows</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-400 mb-1">KES {{ number_format($stats['total_deposits'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500">{{ $stats['deposit_count'] ?? 0 }} transactions</p>
        </div>

        <div class="admin-card p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center">
                    <i class="fas fa-arrow-up text-orange-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Withdrawals</h3>
                    <p class="text-xs text-gray-500">Completed outflows</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-orange-400 mb-1">KES {{ number_format($stats['total_withdrawals'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500">{{ $stats['withdrawal_count'] ?? 0 }} transactions</p>
        </div>

        <div class="admin-card p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-gold/20 flex items-center justify-center">
                    <i class="fas fa-coins text-gold"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Interest Paid</h3>
                    <p class="text-xs text-gray-500">Total payouts to users</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-gold mb-1">KES {{ number_format($stats['total_interest'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500">lifetime</p>
        </div>

        <div class="admin-card p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-hand-holding-usd text-blue-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Loans Disbursed</h3>
                    <p class="text-xs text-gray-500">Active + completed</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-blue-400 mb-1">KES {{ number_format($stats['total_loans'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500">{{ $stats['loans_count'] ?? 0 }} loans</p>
        </div>

        <div class="admin-card p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-dice text-purple-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Lottery Revenue</h3>
                    <p class="text-xs text-gray-500">Bets minus wins</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-purple-400 mb-1">KES {{ number_format($stats['lottery_revenue'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500">{{ $stats['spins_count'] ?? 0 }} spins</p>
        </div>

        <div class="admin-card p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Pending Payouts</h3>
                    <p class="text-xs text-gray-500">Awaiting action</p>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-400 mb-1">KES {{ number_format($stats['pending_payouts'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500">{{ $stats['pending_count'] ?? 0 }} requests</p>
        </div>
    </div>

    {{-- Recent transactions --}}
    <div class="admin-card p-5">
        <h3 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Recent Transactions (Last 20)</h3>
        @if(isset($transactions) && $transactions->count() > 0)
            <table class="w-full text-sm">
                <thead class="border-b border-gray-800 text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="text-left p-2">Date</th>
                        <th class="text-left p-2">User</th>
                        <th class="text-left p-2">Type</th>
                        <th class="text-right p-2">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        <tr class="border-b border-gray-800/50">
                            <td class="p-2 text-xs text-gray-500">{{ $tx->created_at->format('M d, H:i') }}</td>
                            <td class="p-2 text-white">{{ $tx->user->name ?? '—' }}</td>
                            <td class="p-2 text-xs uppercase"><span class="text-gold">{{ $tx->type }}</span></td>
                            <td class="p-2 text-right font-mono">KES {{ number_format($tx->amount ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-500 text-sm">No recent transactions.</p>
        @endif
    </div>

</div>
@endsection
