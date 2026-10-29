@extends('admin.layouts.app')

@section('page-title', 'Dashboard')
@section('content')
@php
    $me = auth()->user();
    $isSuper   = $me->hasRole('super_admin');
    $isFinance = $me->hasRole('super_admin', 'finance');
    $isSupport = $me->hasRole('super_admin', 'support');
@endphp

<div x-data="adminDashboard()" x-init="init()" class="space-y-6">

    @if($isSuper)
        <a href="{{ route('admin.monitoring.index') }}" class="flex items-center justify-between p-3 rounded-lg border transition bg-gray-900 border-gray-800 hover:border-gold/40">
            <div class="flex items-center gap-3">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <div>
                    <p class="text-sm font-semibold text-green-400">All Systems Operational</p>
                    <p class="text-xs text-gray-500">DB up &middot; Cache up</p>
                </div>
            </div>
            <span class="text-xs text-gray-400">View monitoring</span>
        </a>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        @if($isSupport)
            <a href="{{ route('admin.users.index') }}" class="admin-card p-5 hover:ring-2 hover:ring-gold/50 transition">
                <p class="text-sm text-gold-400">Total Users</p>
                <p class="text-3xl font-bold text-gold" x-text="stats.total_users">{{ number_format($stats['total_users'] ?? 0) }}</p>
                <p class="text-xs text-green-400">+<span x-text="stats.new_users_today">{{ $stats['new_users_today'] ?? 0 }}</span> today</p>
            </a>
            <a href="{{ route('admin.kyc.index') }}" class="admin-card p-5 hover:ring-2 hover:ring-gold/50 transition">
                <p class="text-sm text-gold-400">Pending KYC</p>
                <p class="text-3xl font-bold text-blue-400">{{ $stats['pending_kyc'] ?? 0 }}</p>
                <p class="text-xs text-gold-400">Docs to review</p>
            </a>
        @endif
        @if($isFinance)
            <div class="admin-card p-5">
                <p class="text-sm text-gold-400">Total Invested</p>
                <p class="text-3xl font-bold text-gold">KES {{ number_format($stats['total_invested'] ?? 0, 0) }}</p>
                <p class="text-xs text-gray-500">{{ $stats['total_machines'] ?? 0 }} active machines</p>
            </div>
            <a href="{{ route('admin.deposits.index') }}" class="admin-card p-5 hover:ring-2 hover:ring-gold/50 transition">
                <p class="text-sm text-gold-400">Pending Deposits</p>
                <p class="text-3xl font-bold text-yellow-400">{{ $stats['pending_deposits'] ?? 0 }}</p>
                <p class="text-xs text-gold-400">KES {{ number_format($stats['pending_deposits_amount'] ?? 0, 0) }}</p>
            </a>
            <a href="{{ route('admin.withdrawals.index') }}" class="admin-card p-5 hover:ring-2 hover:ring-gold/50 transition">
                <p class="text-sm text-gold-400">Pending Withdrawals</p>
                <p class="text-3xl font-bold text-orange-400">{{ $stats['pending_withdrawals'] ?? 0 }}</p>
                <p class="text-xs text-gold-400">KES {{ number_format($stats['pending_withdrawals_amount'] ?? 0, 0) }}</p>
            </a>
        @endif
        <div class="admin-card p-5">
            <p class="text-sm text-gold-400">Interest Paid</p>
            <p class="text-3xl font-bold text-green-400">KES {{ number_format($stats['total_interest_paid'] ?? 0, 0) }}</p>
            <p class="text-xs text-gray-500">lifetime</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-4">User Growth (30 days)</h3>
            <canvas id="userGrowthChart" height="200"></canvas>
        </div>
        @if($isFinance)
        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-4">Revenue Trend (30 days)</h3>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
        @else
        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-4">Lottery Activity (30 days)</h3>
            <canvas id="lotteryChart" height="200"></canvas>
        </div>
        @endif
    </div>

    <div class="admin-card p-5">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gold">Recent Platform Activity</h3>
            <button @click="refreshStats" class="text-gold-400 hover:text-gold text-sm">
                <i class="fas fa-sync-alt" :class="{'animate-spin': refreshing}"></i> Refresh
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-800 text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="px-2 py-2 text-left">Time</th>
                        <th class="px-2 py-2 text-left">User</th>
                        <th class="px-2 py-2 text-left">Type</th>
                        <th class="px-2 py-2 text-left">Details</th>
                        <th class="px-2 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentActivities ?? [] as $activity)
                        <tr class="border-b border-gray-800/50 hover:bg-gray-800/30">
                            <td class="px-2 py-2 text-gray-400 text-xs">{{ $activity['created_at']?->diffForHumans() ?? '-' }}</td>
                            <td class="px-2 py-2">
                                @if(isset($activity['user_id']))
                                    <a href="{{ route('admin.users.show', $activity['user_id']) }}" class="text-white hover:text-gold">{{ $activity['user'] }}</a>
                                @else
                                    <span class="text-white">{{ $activity['user'] }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-xs font-bold uppercase">
                                @if($activity['type'] == 'deposit')<span class="text-green-400">Deposit</span>
                                @elseif($activity['type'] == 'withdrawal')<span class="text-orange-400">Withdrawal</span>
                                @elseif($activity['type'] == 'loan')<span class="text-blue-400">Loan</span>
                                @else<span class="text-gold">Lottery</span>@endif
                            </td>
                            <td class="px-2 py-2 text-gray-300 text-xs">
                                @if(in_array($activity['type'], ['deposit', 'withdrawal', 'loan']))
                                    KES {{ number_format($activity['amount'] ?? 0, 2) }}
                                @else
                                    Bet: KES {{ number_format($activity['bet'] ?? 0, 2) }} - Win: KES {{ number_format($activity['win'] ?? 0, 2) }}
                                @endif
                            </td>
                            <td class="px-2 py-2">
                                @if(isset($activity['status']))
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $activity['status'] == 'pending' ? 'bg-yellow-500/20 text-yellow-400' : (in_array($activity['status'], ['verified','completed','active','approved']) ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400') }}">{{ ucfirst($activity['status']) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-gray-500">No activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function adminDashboard() {
    return {
        stats: {
            total_users: {{ $stats['total_users'] ?? 0 }},
            new_users_today: {{ $stats['new_users_today'] ?? 0 }},
        },
        refreshing: false,
        init() {
            this.initCharts();
            setInterval(() => this.refreshStats(), 60000);
        },
        initCharts() {
            const goldColor = '#D4AF37';
            
            // User Growth
            const c1 = document.getElementById('userGrowthChart')?.getContext('2d');
            if (c1) new Chart(c1, { 
                type: 'line', 
                data: { 
                    labels: {!! json_encode(array_keys($userGrowth ?? [])) !!}, 
                    datasets: [{ label: 'New Users', data: {!! json_encode(array_values($userGrowth ?? [])) !!}, borderColor: goldColor, backgroundColor: 'rgba(212,175,55,0.1)', fill: true, tension: 0.4 }] 
                }, 
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { color: goldColor } }, x: { ticks: { color: goldColor } } } } 
            });

            // Revenue Trend
            const c2 = document.getElementById('revenueChart')?.getContext('2d');
            if (c2) new Chart(c2, { 
                type: 'bar', 
                data: { 
                    labels: {!! json_encode($revenueTrend['labels'] ?? []) !!}, 
                    datasets: [{ label: 'Revenue (KES)', data: {!! json_encode($revenueTrend['data'] ?? []) !!}, backgroundColor: goldColor, borderRadius: 6 }] 
                }, 
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { color: goldColor } }, x: { ticks: { color: goldColor } } } } 
            });

            // Lottery Activity
            const c3 = document.getElementById('lotteryChart')?.getContext('2d');
            if (c3) new Chart(c3, { 
                type: 'line', 
                data: { 
                    labels: {!! json_encode($lotteryActivity['labels'] ?? []) !!}, 
                    datasets: [
                        { label: 'Spins', data: {!! json_encode($lotteryActivity['spins'] ?? []) !!}, borderColor: '#FFD700', yAxisID: 'y', tension: 0.4 }, 
                        { label: 'Bets', data: {!! json_encode($lotteryActivity['bets'] ?? []) !!}, borderColor: goldColor, backgroundColor: 'rgba(212,175,55,0.15)', fill: true, yAxisID: 'y1', tension: 0.4 }
                    ] 
                }, 
                options: { responsive: true, plugins: { legend: { labels: { color: goldColor } } }, scales: { y: { ticks: { color: '#FFD700' } }, y1: { position: 'right', ticks: { color: goldColor }, grid: { drawOnChartArea: false } }, x: { ticks: { color: goldColor } } } } 
            });
        },
        async refreshStats() {
            this.refreshing = true;
            try { 
                const r = await fetch('{{ route("admin.stats") }}'); 
                if (r.ok) { 
                    const data = await r.json(); 
                    this.stats = data;
                } 
            } catch(e) {
                console.error('Failed to refresh stats', e);
            } finally { 
                this.refreshing = false; 
            }
        },
    };
}
</script>
@endsection
