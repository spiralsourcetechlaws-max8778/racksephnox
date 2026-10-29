@extends('admin.layouts.app')

@section('page-title', 'Dashboard')
@section('content')

<div class="space-y-6">

    {{-- ═══ SYSTEM HEALTH BANNER ═══ --}}
    @php
        $dbUp = true; $cacheUp = true; $pendingMigs = 0;
        try { \DB::connection()->getPdo(); } catch (\Throwable $e) { $dbUp = false; }
        try { \Cache::put('dash_ping', '1', 5); $cacheUp = \Cache::get('dash_ping') === '1'; \Cache::forget('dash_ping'); } catch (\Throwable $e) { $cacheUp = false; }
        try { \Artisan::call('migrate:status'); $pendingMigs = substr_count(\Artisan::output(), 'Pending'); } catch (\Throwable $e) {}
        $critical = !$dbUp || !$cacheUp;
    @endphp

    @if(auth()->user()->hasRole('super_admin'))
        <a href="{{ route('admin.monitoring.index') }}" class="flex items-center justify-between p-3 rounded-lg border transition {{ $critical ? 'bg-red-500/10 border-red-500/40' : 'bg-gray-900 border-gray-800 hover:border-gold/40' }}">
            <div class="flex items-center gap-3">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $critical ? 'bg-red-400' : 'bg-green-400' }} opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 {{ $critical ? 'bg-red-500' : 'bg-green-500' }}"></span>
                </span>
                <div>
                    <p class="text-sm font-semibold {{ $critical ? 'text-red-400' : 'text-green-400' }}">{{ $critical ? 'System Degraded' : 'All Systems Operational' }}</p>
                    <p class="text-xs text-gray-500">DB {{ $dbUp ? 'up' : 'DOWN' }} · Cache {{ $cacheUp ? 'up' : 'DOWN' }}@if($pendingMigs > 0) · <span class="text-yellow-400">{{ $pendingMigs }} pending migrations</span>@endif</p>
                </div>
            </div>
            <span class="text-xs text-gray-400">View monitoring →</span>
        </a>
    @endif

    {{-- ═══ QUICK ACTION BANNERS (finance) ═══ --}}
    @if(auth()->user()->hasRole('super_admin', 'finance') && (($stats['pending_deposits'] ?? 0) + ($stats['pending_withdrawals'] ?? 0)) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @if(($stats['pending_deposits'] ?? 0) > 0)
                <a href="{{ route('admin.deposits.index') }}" class="flex items-center justify-between bg-yellow-500/10 border border-yellow-500/30 p-4 rounded-lg hover:bg-yellow-500/20 transition">
                    <div>
                        <p class="text-xs text-yellow-400 uppercase font-semibold">Pending Deposits</p>
                        <p class="text-2xl font-bold text-yellow-300">{{ $stats['pending_deposits'] }}</p>
                        <p class="text-xs text-yellow-500/80">KES {{ number_format($stats['pending_deposits_amount'] ?? 0, 2) }} to review</p>
                    </div>
                    <i class="fas fa-arrow-right text-yellow-400 text-xl"></i>
                </a>
            @endif
            @if(($stats['pending_withdrawals'] ?? 0) > 0)
                <a href="{{ route('admin.withdrawals.index') }}" class="flex items-center justify-between bg-orange-500/10 border border-orange-500/30 p-4 rounded-lg hover:bg-orange-500/20 transition">
                    <div>
                        <p class="text-xs text-orange-400 uppercase font-semibold">Pending Withdrawals</p>
                        <p class="text-2xl font-bold text-orange-300">{{ $stats['pending_withdrawals'] }}</p>
                        <p class="text-xs text-orange-500/80">KES {{ number_format($stats['pending_withdrawals_amount'] ?? 0, 2) }} to review</p>
                    </div>
                    <i class="fas fa-arrow-right text-orange-400 text-xl"></i>
                </a>
            @endif
        </div>
    @endif

    {{-- ═══ KPI CARDS ═══ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Total Users</p>
            <p class="text-2xl font-bold text-white mt-1">{{ number_format($stats['total_users'] ?? 0) }}</p>
            <p class="text-xs text-green-400 mt-1">+{{ $stats['new_users_today'] ?? 0 }} today</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Total Invested</p>
            <p class="text-2xl font-bold text-gold mt-1">KES {{ number_format($stats['total_invested'] ?? 0, 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">Machines: KES {{ number_format($stats['total_machine_invested'] ?? 0, 0) }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Interest Paid</p>
            <p class="text-2xl font-bold text-green-400 mt-1">KES {{ number_format($stats['total_interest_paid'] ?? 0, 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">Referral bonuses: KES {{ number_format($stats['total_referral_bonus'] ?? 0, 0) }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Pending KYC</p>
            <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['pending_kyc'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Awaiting review</p>
        </div>
    </div>

    {{-- ═══ CHARTS ROW ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-gold mb-3">Revenue Trend (30 days)</h3>
            <div style="height: 220px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-gold mb-3">User Growth (30 days)</h3>
            <div style="height: 220px;">
                <canvas id="userGrowthChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ═══ LOAN STATS ═══ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Active Loans</p>
            <p class="text-xl font-bold text-white mt-1">{{ $loanStats['active_loans'] ?? 0 }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Outstanding</p>
            <p class="text-xl font-bold text-orange-400 mt-1">KES {{ number_format($loanStats['total_outstanding'] ?? 0, 0) }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Collected</p>
            <p class="text-xl font-bold text-green-400 mt-1">KES {{ number_format($loanStats['total_collected'] ?? 0, 0) }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-400 uppercase">Overdue</p>
            <p class="text-xl font-bold text-red-400 mt-1">{{ $loanStats['overdue_repayments'] ?? 0 }}</p>
        </div>
    </div>

    {{-- ═══ RECENT ACTIVITY ═══ --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-gold mb-3">Recent Activity</h3>
        @if(!empty($recentActivities))
            <div class="space-y-2">
                @foreach($recentActivities as $a)
                    <div class="flex items-center justify-between py-2 border-b border-gray-800/50 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center
                                @if(($a['type'] ?? '') === 'deposit') bg-green-500/20 text-green-400
                                @elseif(($a['type'] ?? '') === 'withdrawal') bg-red-500/20 text-red-400
                                @elseif(($a['type'] ?? '') === 'loan') bg-orange-500/20 text-orange-400
                                @else bg-gold/20 text-gold @endif">
                                <i class="fas
                                    @if(($a['type'] ?? '') === 'deposit') fa-arrow-down
                                    @elseif(($a['type'] ?? '') === 'withdrawal') fa-arrow-up
                                    @elseif(($a['type'] ?? '') === 'loan') fa-hand-holding-usd
                                    @else fa-dice @endif text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm text-white">{{ $a['user'] ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ ucfirst($a['type'] ?? '') }} · {{ \Carbon\Carbon::parse($a['created_at'] ?? now())->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if(isset($a['amount']))
                                <p class="text-sm font-medium text-white">KES {{ number_format($a['amount'], 2) }}</p>
                            @elseif(isset($a['bet']))
                                <p class="text-sm font-medium text-white">Bet: KES {{ number_format($a['bet'], 2) }}</p>
                            @endif
                            <p class="text-xs text-gray-500">{{ ucfirst($a['status'] ?? '') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">No recent activity.</p>
        @endif
    </div>

    {{-- ═══ RECENT LISTS ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-gold mb-3">Latest Users</h3>
            @forelse($recentUsers ?? [] as $u)
                <div class="flex items-center justify-between py-2 border-b border-gray-800/50 last:border-0">
                    <div>
                        <p class="text-sm text-white">{{ $u->name }}</p>
                        <p class="text-xs text-gray-500">{{ $u->email }}</p>
                    </div>
                    <span class="text-xs text-gray-400">{{ optional($u->created_at)->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">No users yet.</p>
            @endforelse
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-gold mb-3">Recent Loans</h3>
            @forelse($recentLoans ?? [] as $l)
                <div class="flex items-center justify-between py-2 border-b border-gray-800/50 last:border-0">
                    <div>
                        <p class="text-sm text-white">{{ $l->user?->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-500">{{ $l->product?->name ?? 'Loan' }} · {{ ucfirst($l->status) }}</p>
                    </div>
                    <p class="text-sm font-medium text-gold">KES {{ number_format($l->principal ?? 0, 0) }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500">No loans yet.</p>
            @endforelse
        </div>
    </div>

    {{-- ═══ REVENUE TARGET ═══ --}}
    @if(isset($revenueTarget) && $targetProgress !== null)
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-gold">Revenue Target</h3>
                <span class="text-xs text-gray-400">{{ $daysLeft }} days remaining</span>
            </div>
            <div class="w-full bg-gray-800 rounded-full h-3 overflow-hidden">
                <div class="bg-gold h-3 rounded-full transition-all" style="width: {{ min(100, $targetProgress) }}%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-gray-400">
                <span>KES {{ number_format($revenueTarget->current_revenue, 0) }} of KES {{ number_format($revenueTarget->target_amount, 0) }}</span>
                <span class="text-gold font-semibold">{{ number_format($targetProgress, 1) }}%</span>
            </div>
        </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af' } },
            x: { grid: { display: false }, ticks: { color: '#9ca3af', maxRotation: 45, minRotation: 45 } },
        }
    };

    try {
        const revenueEl = document.getElementById('revenueChart');
        if (revenueEl) {
            new Chart(revenueEl, {
                type: 'line',
                data: {
                    labels: {!! json_encode($revenueTrend['labels'] ?? []) !!},
                    datasets: [{
                        label: 'Revenue (KES)',
                        data: {!! json_encode($revenueTrend['data'] ?? []) !!},
                        borderColor: '#D4AF37',
                        backgroundColor: 'rgba(212,175,55,0.15)',
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: chartDefaults,
            });
        }
    } catch(e) { console.error('Revenue chart failed', e); }

    try {
        const growthEl = document.getElementById('userGrowthChart');
        if (growthEl) {
            const growthData = @json($userGrowth ?? []);
            new Chart(growthEl, {
                type: 'bar',
                data: {
                    labels: Object.keys(growthData),
                    datasets: [{
                        label: 'New Users',
                        data: Object.values(growthData),
                        backgroundColor: '#D4AF37',
                    }]
                },
                options: chartDefaults,
            });
        }
    } catch(e) { console.error('User growth chart failed', e); }
});
</script>
@endsection
