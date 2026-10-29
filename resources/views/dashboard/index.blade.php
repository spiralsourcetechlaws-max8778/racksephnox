@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold golden-title">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-gray-400 mt-1">Here is your financial overview for today.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('deposit.form') }}" class="btn-golden py-2 px-4 text-sm">Deposit Funds</a>
            <a href="{{ route('withdrawal.form') }}" class="btn-outline-silver py-2 px-4 text-sm">Withdraw</a>
        </div>
    </div>

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded-lg text-sm">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded-lg text-sm">{{ $errors->first() }}</div> @endif

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card-golden p-5">
            <p class="text-xs text-gold-400 uppercase tracking-wider">Wallet Balance</p>
            <p class="text-2xl font-bold text-white mt-1">KES {{ number_format($walletBalance ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">Available to invest</p>
        </div>
        <div class="card-golden p-5">
            <p class="text-xs text-gold-400 uppercase tracking-wider">Total Invested</p>
            <p class="text-2xl font-bold text-gold mt-1">KES {{ number_format($totalInvested ?? 0, 2) }}</p>
            <p class="text-xs text-green-400 mt-2">{{ $activeInvestments ?? 0 }} active plans</p>
        </div>
        <div class="card-golden p-5">
            <p class="text-xs text-gold-400 uppercase tracking-wider">Total Profit</p>
            <p class="text-2xl font-bold text-green-400 mt-1">KES {{ number_format($totalProfit ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">ROI: {{ $roi ?? 0 }}%</p>
        </div>
        <div class="card-golden p-5 border-l-4 border-l-purple-500">
            <p class="text-xs text-purple-400 uppercase tracking-wider">Lottery Jackpot</p>
            <p class="text-2xl font-bold text-purple-300 mt-1">KES {{ number_format($lotteryJackpot ?? 1000, 0) }}</p>
            <a href="{{ route('lottery.index') }}" class="text-xs text-gold-400 hover:text-gold mt-2 inline-block">Play Now →</a>
        </div>
    </div>

    {{-- CHARTS ROW --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card-golden p-5 lg:col-span-2">
            <h3 class="text-sm font-semibold text-gold mb-4">Profit History (30 Days)</h3>
            <div style="height: 250px;"><canvas id="profitChart"></canvas></div>
        </div>
        <div class="card-golden p-5">
            <h3 class="text-sm font-semibold text-gold mb-4">Portfolio Breakdown</h3>
            <div style="height: 250px;"><canvas id="portfolioChart"></canvas></div>
        </div>
    </div>

    {{-- MID SECTION --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- RECENT TRANSACTIONS --}}
        <div class="card-golden p-5 lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-semibold text-gold">Recent Transactions</h3>
                <a href="{{ route('transactions.index') }}" class="text-xs text-gold-400 hover:text-gold">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gold/20 text-gray-400 text-xs uppercase">
                        <tr><th class="text-left py-2">Date</th><th class="text-left py-2">Type</th><th class="text-left py-2">Description</th><th class="text-right py-2">Amount</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($recentTransactions ?? [] as $tx)
                            <tr>
                                <td class="py-3 text-gray-400 text-xs">{{ optional($tx->created_at)->format('M d, H:i') ?? '-' }}</td>
                                <td class="py-3 text-gold text-xs">{{ ucfirst($tx->type ?? '-') }}</td>
                                <td class="py-3 text-gray-300 text-xs">{{ \Illuminate\Support\Str::limit($tx->description ?? '-', 40) }}</td>
                                <td class="py-3 text-right text-xs {{ ($tx->amount ?? 0) > 0 ? 'text-green-400' : 'text-red-400' }}">KES {{ number_format($tx->amount ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-gray-500">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- LOANS & QUICK ACTIONS --}}
        <div class="space-y-6">
            <div class="card-golden p-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-semibold text-gold">Loans Overview</h3>
                    <a href="{{ route('loans.index') }}" class="text-xs text-gold-400 hover:text-gold">Manage</a>
                </div>
                <p class="text-xs text-gray-400">Total Outstanding</p>
                <p class="text-xl font-bold text-orange-400">KES {{ number_format($loanStats['total_outstanding'] ?? 0, 2) }}</p>
                <div class="flex justify-between mt-3 text-xs text-gray-500">
                    <span>Active: {{ $loanStats['active_count'] ?? 0 }}</span>
                    <span>Pending: {{ $loanStats['pending_count'] ?? 0 }}</span>
                </div>
            </div>

            <div class="card-golden p-5">
                <h3 class="text-sm font-semibold text-gold mb-3">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('machines.index') }}" class="bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-center transition">
                        <i class="fas fa-microchip text-gold mb-1"></i><p class="text-xs text-white">Invest</p>
                    </a>
                    <a href="{{ route('trading.index') }}" class="bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-center transition">
                        <i class="fas fa-chart-line text-gold mb-1"></i><p class="text-xs text-white">Trade</p>
                    </a>
                    <a href="{{ route('referrals') }}" class="bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-center transition">
                        <i class="fas fa-users text-gold mb-1"></i><p class="text-xs text-white">Referrals</p>
                    </a>
                    <a href="{{ route('kyc') }}" class="bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-center transition">
                        <i class="fas fa-id-card text-gold mb-1"></i><p class="text-xs text-white">KYC</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const goldColor = '#D4AF37';
    const chartDefaults = {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af' } }, x: { grid: { display: false }, ticks: { color: '#9ca3af', maxRotation: 45 } } }
    };

    try {
        new Chart(document.getElementById('profitChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($profitHistory['labels'] ?? []) !!},
                datasets: [{ label: 'Profit', data: {!! json_encode($profitHistory['data'] ?? []) !!}, borderColor: goldColor, backgroundColor: 'rgba(212,175,55,0.15)', fill: true, tension: 0.4 }]
            },
            options: chartDefaults
        });
    } catch(e) { console.error('Profit chart failed', e); }

    try {
        new Chart(document.getElementById('portfolioChart'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($portfolio['labels'] ?? []) !!},
                datasets: [{ data: {!! json_encode($portfolio['data'] ?? []) !!}, backgroundColor: ['#D4AF37', '#FFD700', '#B8860B', '#8B6914', '#4B3621'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: '#9ca3af' } } } }
        });
    } catch(e) { console.error('Portfolio chart failed', e); }
});
</script>
@endsection
