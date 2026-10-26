@extends('admin.layouts.app')

@section('content')
@php
    $me = auth()->user();
    $isSuper   = $me->hasRole('super_admin');
    $isFinance = $me->hasRole('super_admin', 'finance');
    $isSupport = $me->hasRole('super_admin', 'support');
@endphp

<div x-data="adminDashboard()" x-init="init()" class="space-y-6">

    {{-- ==================== ALERTS / ACTION REQUIRED ==================== --}}
    @if($isFinance && (($stats['pending_deposits'] ?? 0) + ($stats['pending_withdrawals'] ?? 0)) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @if(($stats['pending_deposits'] ?? 0) > 0)
                <a href="{{ route('admin.deposits.index') }}" class="flex items-center justify-between bg-yellow-500/10 border border-yellow-500/30 hover:border-yellow-500/60 p-4 rounded-lg transition">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                        <div>
                            <p class="text-yellow-400 font-semibold">{{ $stats['pending_deposits'] }} pending deposit(s)</p>
                            <p class="text-xs text-gray-400">KES {{ number_format($stats['pending_deposits_amount'] ?? 0, 2) }} awaiting verification</p>
                        </div>
                    </div>
                    <span class="text-yellow-400 text-sm font-medium">Review →</span>
                </a>
            @endif
            @if(($stats['pending_withdrawals'] ?? 0) > 0)
                <a href="{{ route('admin.withdrawals.index') }}" class="flex items-center justify-between bg-orange-500/10 border border-orange-500/30 hover:border-orange-500/60 p-4 rounded-lg transition">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-orange-400 text-xl"></i>
                        <div>
                            <p class="text-orange-400 font-semibold">{{ $stats['pending_withdrawals'] }} pending withdrawal(s)</p>
                            <p class="text-xs text-gray-400">KES {{ number_format($stats['pending_withdrawals_amount'] ?? 0, 2) }} awaiting processing</p>
                        </div>
                    </div>
                    <span class="text-orange-400 text-sm font-medium">Review →</span>
                </a>
            @endif
        </div>
    @endif

    @if($isSupport && ($stats['pending_kyc'] ?? 0) > 0)
        <a href="{{ route('admin.kyc.index') }}" class="flex items-center justify-between bg-blue-500/10 border border-blue-500/30 hover:border-blue-500/60 p-4 rounded-lg transition">
            <div class="flex items-center gap-3">
                <i class="fas fa-id-card text-blue-400 text-xl"></i>
                <div>
                    <p class="text-blue-400 font-semibold">{{ $stats['pending_kyc'] }} KYC document(s) pending review</p>
                    <p class="text-xs text-gray-400">Approve or reject to unblock users</p>
                </div>
            </div>
            <span class="text-blue-400 text-sm font-medium">Review →</span>
        </a>
    @endif

    {{-- ==================== ROLE BANNER ==================== --}}
    @if(!$isSuper)
        <div class="flex items-center gap-3 bg-gray-900 border border-gray-800 p-3 rounded-lg">
            <i class="fas fa-shield-alt text-{{ $isFinance ? 'green' : 'blue' }}-400"></i>
            <p class="text-sm text-gray-400">
                You are signed in as <span class="font-semibold text-white">{{ $me->role_label }}</span>.
                Your access is limited to
                @if($isFinance) deposits, withdrawals, loans, reports, and wallet adjustments.
                @else user views, KYC review, and dashboard analytics.
                @endif
            </p>
        </div>
    @endif

    {{-- ==================== TOP STATS ROW (role-aware) ==================== --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">

        @if($isSupport)
            {{-- Support / Super --}}
            <a href="{{ route('admin.users.index') }}" class="admin-card p-5 hover:ring-2 hover:ring-gold/50 transition">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm text-gold-400">Total Users</p>
                        <p class="text-3xl font-bold text-gold">{{ number_format($stats['total_users'] ?? 0) }}</p>
                        <p class="text-xs text-green-400">+{{ number_format($stats['new_users_today'] ?? 0) }} today</p>
                    </div>
                    <i class="fas fa-users text-3xl text-gold/40"></i>
                </div>
            </a>

            <a href="{{ route('admin.kyc.index') }}" class="admin-card p-5 hover:ring-2 hover:ring-gold/50 transition">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm text-gold-400">Pending KYC</p>
                        <p class="text-3xl font-bold text-blue-400">{{ $stats['pending_kyc'] ?? 0 }}</p>
                        <p class="text-xs text-gold-400">Docs to review</p>
                    </div>
                    <i class="fas fa-id-card text-3xl text-gold/40"></i>
                </div>
            </a>
        @endif

        @if($isFinance)
            {{-- Finance / Super --}}
            <div class="admin-card p-5">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm text-gold-400">Total Invested</p>
                        <p class="text-3xl font-bold text-gold">KES {{ number_format($stats['total_invested'] ?? 0, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ $stats['total_machines'] ?? 0 }} active machines</p>
                    </div>
                    <i class="fas fa-chart-line text-3xl text-gold/40"></i>
                </div>
            </div>

            <a href="{{ route('admin.deposits.index') }}" class="admin-card p-5 hover:ring-2 hover:ring-gold/50 transition">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm text-gold-400">Pending Deposits</p>
                        <p class="text-3xl font-bold text-yellow-400">{{ $stats['pending_deposits'] ?? 0 }}</p>
                        <p class="text-xs text-gold-400">KES {{ number_format($stats['pending_deposits_amount'] ?? 0, 2) }}</p>
                    </div>
                    <i class="fas fa-clock text-3xl text-gold/40"></i>
                </div>
            </a>

            <a href="{{ route('admin.withdrawals.index') }}" class="admin-card p-5 hover:ring-2 hover:ring-gold/50 transition">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm text-gold-400">Pending Withdrawals</p>
                        <p class="text-3xl font-bold text-orange-400">{{ $stats['pending_withdrawals'] ?? 0 }}</p>
                        <p class="text-xs text-gold-400">KES {{ number_format($stats['pending_withdrawals_amount'] ?? 0, 2) }}</p>
                    </div>
                    <i class="fas fa-clock text-3xl text-gold/40"></i>
                </div>
            </a>
        @endif

        {{-- Every admin --}}
        <div class="admin-card p-5">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm text-gold-400">Interest Paid</p>
                    <p class="text-3xl font-bold text-green-400">KES {{ number_format($stats['total_interest_paid'] ?? 0, 2) }}</p>
                    <p class="text-xs text-gray-500">lifetime payouts</p>
                </div>
                <i class="fas fa-coins text-3xl text-gold/40"></i>
            </div>
        </div>
    </div>

    {{-- ==================== LOAN DOMAIN (finance / super only) ==================== --}}
    @if($isFinance)
    <div class="admin-card p-5">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gold">💵 Loan Portfolio</h3>
            <a href="{{ route('admin.loans.index') }}" class="text-xs text-gold-400 hover:text-gold">Manage Loans →</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-gray-900/50 p-3 rounded">
                <p class="text-xs text-gray-400 uppercase">Pending</p>
                <p class="text-xl font-bold text-yellow-400">{{ $loanStats['pending_loans'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">KES {{ number_format($loanStats['pending_loans_amount'] ?? 0, 0) }}</p>
            </div>
            <div class="bg-gray-900/50 p-3 rounded">
                <p class="text-xs text-gray-400 uppercase">Active</p>
                <p class="text-xl font-bold text-green-400">{{ $loanStats['active_loans'] ?? 0 }}</p>
            </div>
            <div class="bg-gray-900/50 p-3 rounded">
                <p class="text-xs text-gray-400 uppercase">Outstanding</p>
                <p class="text-xl font-bold text-red-400">KES {{ number_format($loanStats['total_outstanding'] ?? 0, 0) }}</p>
            </div>
            <div class="bg-gray-900/50 p-3 rounded">
                <p class="text-xs text-gray-400 uppercase">Collected</p>
                <p class="text-xl font-bold text-gold">KES {{ number_format($loanStats['total_collected'] ?? 0, 0) }}</p>
            </div>
            <div class="bg-gray-900/50 p-3 rounded">
                <p class="text-xs text-gray-400 uppercase">Overdue</p>
                <p class="text-xl font-bold text-red-600">{{ $loanStats['overdue_repayments'] ?? 0 }}</p>
                <p class="text-xs text-red-400/70">KES {{ number_format($loanStats['overdue_amount'] ?? 0, 0) }}</p>
            </div>
            <div class="bg-gray-900/50 p-3 rounded">
                <p class="text-xs text-gray-400 uppercase">Completed</p>
                <p class="text-xl font-bold text-white">{{ $loanStats['completed_loans'] ?? 0 }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== REVENUE TARGET (finance / super only) ==================== --}}
    @if($isFinance)
    <div class="admin-card p-5">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gold">💰 {{ $daysLeft ?? 26 }}-Day Revenue Target</h3>
            <span class="text-xs text-gray-500">{{ $daysLeft ?? 26 }} days remaining</span>
        </div>
        <div class="flex justify-between items-baseline mb-2">
            <span class="text-2xl font-bold text-gold">KES {{ number_format($revenueTarget->current_revenue ?? 0, 2) }}</span>
            <span class="text-gray-500 text-sm">/ KES {{ number_format($revenueTarget->target_amount ?? 0, 2) }}</span>
        </div>
        <div class="w-full bg-gray-800 rounded-full h-3 mb-2">
            <div class="bg-gradient-to-r from-gold to-green-500 h-3 rounded-full transition-all duration-500" style="width: {{ min(100, $targetProgress ?? 0) }}%"></div>
        </div>
        <p class="text-sm text-gray-400">Remaining: KES {{ number_format($remaining ?? 0, 2) }}</p>
        @if(($remaining ?? 0) <= 0)
            <p class="text-green-400 text-sm mt-2 font-semibold">🎯 Target achieved!</p>
        @endif
    </div>
    @endif

    {{-- ==================== CHARTS (everyone, read-only) ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-4">👥 User Growth (30 days)</h3>
            <canvas id="userGrowthChart" height="200"></canvas>
        </div>
        @if($isFinance)
        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-4">💰 Revenue Trend (30 days)</h3>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
        @else
        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-4">🎲 Lottery Activity (30 days)</h3>
            <canvas id="lotteryChart" height="200"></canvas>
        </div>
        @endif
    </div>

    @if($isFinance)
    <div class="admin-card p-5">
        <h3 class="text-lg font-semibold text-gold mb-4">🎲 Lottery Activity (30 days)</h3>
        <canvas id="lotteryChart" height="200"></canvas>
    </div>
    @endif

    {{-- ==================== RECENT LOANS (finance / super only) ==================== --}}
    @if($isFinance && ($recentLoans->count() ?? 0) > 0)
    <div class="admin-card p-5">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gold">💸 Recent Loan Applications</h3>
            <a href="{{ route('admin.loans.index') }}" class="text-gold-400 text-sm hover:text-gold">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-800 text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="text-left p-2">Reference</th>
                        <th class="text-left p-2">User</th>
                        <th class="text-left p-2">Product</th>
                        <th class="text-right p-2">Amount</th>
                        <th class="text-left p-2">Status</th>
                        <th class="text-right p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLoans as $loan)
                        <tr class="border-b border-gray-800/50 hover:bg-gray-800/30">
                            <td class="p-2 font-mono text-xs text-gray-400">{{ $loan->reference ?? '—' }}</td>
                            <td class="p-2 text-white">{{ $loan->user->name ?? '—' }}</td>
                            <td class="p-2 text-gray-400">{{ $loan->product->name ?? $loan->product_name ?? '—' }}</td>
                            <td class="p-2 text-right text-gold">KES {{ number_format($loan->principal ?? 0, 0) }}</td>
                            <td class="p-2">
                                <span class="px-2 py-0.5 text-xs rounded {{ in_array($loan->status, ['approved', 'active', 'completed']) ? 'bg-green-500/20 text-green-400' : ($loan->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400') }}">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </td>
                            <td class="p-2 text-right">
                                <a href="{{ route('admin.loans.show', $loan) }}" class="text-gold-400 hover:text-gold text-xs">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ==================== RECENT ACTIVITY FEED (everyone) ==================== --}}
    <div class="admin-card p-5">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gold">📋 Recent Platform Activity</h3>
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
                            <td class="px-2 py-2 text-gray-400 text-xs">{{ $activity['created_at']?->diffForHumans() ?? '—' }}</td>
                            <td class="px-2 py-2 text-white">{{ $activity['user'] }}</td>
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
                                    Bet: KES {{ number_format($activity['bet'] ?? 0, 2) }} · Win: KES {{ number_format($activity['win'] ?? 0, 2) }}
                                @endif
                            </td>
                            <td class="px-2 py-2">
                                @if(isset($activity['status']))
                                    <span class="px-2 py-0.5 rounded-full text-xs
                                        @if($activity['status'] == 'pending') bg-yellow-500/20 text-yellow-400
                                        @elseif(in_array($activity['status'], ['verified', 'completed', 'active', 'approved'])) bg-green-500/20 text-green-400
                                        @else bg-red-500/20 text-red-400 @endif">
                                        {{ ucfirst($activity['status']) }}
                                    </span>
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
        refreshing: false,
        init() {
            this.initCharts();
            setInterval(() => this.refreshStats(), 60000);
        },
        initCharts() {
            const goldColor = '#D4AF37';

            const ctx1 = document.getElementById('userGrowthChart')?.getContext('2d');
            if (ctx1) new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_keys($userGrowth ?? [])) !!},
                    datasets: [{
                        label: 'New Users',
                        data: {!! json_encode(array_values($userGrowth ?? [])) !!},
                        borderColor: goldColor,
                        backgroundColor: 'rgba(212,175,55,0.1)',
                        fill: true,
                        tension: 0.4,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { ticks: { color: goldColor }, grid: { color: 'rgba(212,175,55,0.1)' } },
                        x: { ticks: { color: goldColor }, grid: { display: false } },
                    },
                },
            });

            const ctx2 = document.getElementById('revenueChart')?.getContext('2d');
            if (ctx2) new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($revenueTrend['labels'] ?? []) !!},
                    datasets: [{
                        label: 'Revenue (KES)',
                        data: {!! json_encode($revenueTrend['data'] ?? []) !!},
                        backgroundColor: goldColor,
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { ticks: { color: goldColor }, grid: { color: 'rgba(212,175,55,0.1)' } },
                        x: { ticks: { color: goldColor }, grid: { display: false } },
                    },
                },
            });

            const ctx3 = document.getElementById('lotteryChart')?.getContext('2d');
            if (ctx3) new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: {!! json_encode($lotteryActivity['labels'] ?? []) !!},
                    datasets: [
                        { label: 'Spins', data: {!! json_encode($lotteryActivity['spins'] ?? []) !!}, borderColor: '#FFD700', yAxisID: 'y', tension: 0.4 },
                        { label: 'Bets (KES)', data: {!! json_encode($lotteryActivity['bets'] ?? []) !!}, borderColor: goldColor, backgroundColor: 'rgba(212,175,55,0.15)', fill: true, yAxisID: 'y1', tension: 0.4 },
                    ],
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { labels: { color: goldColor } } },
                    scales: {
                        y: { ticks: { color: '#FFD700' }, grid: { color: 'rgba(255,215,0,0.1)' } },
                        y1: { position: 'right', ticks: { color: goldColor }, grid: { drawOnChartArea: false } },
                        x: { ticks: { color: goldColor }, grid: { display: false } },
                    },
                },
            });
        },
        async refreshStats() {
            this.refreshing = true;
            try {
                const res = await fetch('{{ route("admin.stats") }}');
                if (res.ok) {
                    await res.json();
                }
            } catch (e) {
                console.error('Stats refresh failed:', e);
            } finally {
                this.refreshing = false;
            }
        },
    };
}
</script>
@endsection
