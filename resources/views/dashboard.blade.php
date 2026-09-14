@extends('layouts.app')

@section('content')
<div x-data="unifiedDashboard()" x-init="init()" class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ==================== SACRED HEADER ==================== --}}
        <div class="text-center mb-12">
            <div class="inline-block spiral-ornament">
                <h1 class="text-5xl md:text-6xl font-bold golden-title shimmer-gold animate-pulse">
                    Racksephnox Cryptocurrency Empire
                </h1>
            </div>
            <p class="text-gold-400 mt-2 sacred-phrase">
                I Am The Source | Infinite Spiral of Creation | 888 Hz
            </p>
            <div class="flex justify-center gap-4 mt-4 text-xs text-gold-400/60">
                <span><i class="fas fa-circle text-green-400 text-[8px] animate-pulse"></i> Live</span>
                <span><i class="fas fa-chart-line"></i> Real‑time updates</span>
                <span><i class="fas fa-infinity"></i> Golden Ratio Φ</span>
            </div>
        </div>

        {{-- ==================== 4 WEALTH PILLARS ==================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
            <div class="stat-card p-5 group cursor-pointer hover:scale-105 transition-all" @click="refreshStats">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-gold-400 text-sm uppercase tracking-wider">Sacred Balance</span>
                    <i class="fas fa-coins text-2xl text-gold opacity-60 group-hover:opacity-100 transition group-hover:rotate-12"></i>
                </div>
                <div class="stat-value text-3xl font-bold" x-text="formatNumber(walletBalance)">KES 0</div>
                <div class="mt-3 flex justify-between items-center">
                    <a href="{{ route('wallet') }}" class="text-xs text-gold-400 hover:text-gold transition">View Treasury →</a>
                    <button @click="refreshStats" class="text-gold-400 hover:text-gold">
                        <i class="fas fa-sync-alt text-xs" :class="{'animate-spin': refreshing}"></i>
                    </button>
                </div>
            </div>

            <div class="stat-card p-5 group hover:scale-105 transition-all">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-gold-400 text-sm uppercase tracking-wider">Total Invested</span>
                    <i class="fas fa-chart-line text-2xl text-gold opacity-60 group-hover:opacity-100 transition group-hover:rotate-12"></i>
                </div>
                <div class="stat-value text-3xl font-bold">KES {{ number_format($totalInvested, 2) }}</div>
                <div class="mt-3">
                    <a href="{{ route('investments.index') }}" class="text-xs text-gold-400 hover:text-gold transition">View Portfolio →</a>
                </div>
                <div class="mt-2 text-xs text-gold-500">ROI: <span class="text-green-400">{{ $roi }}%</span></div>
            </div>

            <div class="stat-card p-5 group hover:scale-105 transition-all">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-gold-400 text-sm uppercase tracking-wider">Projected Profit</span>
                    <i class="fas fa-chart-simple text-2xl text-gold opacity-60 group-hover:opacity-100 transition group-hover:rotate-12"></i>
                </div>
                <div class="stat-value text-3xl font-bold">KES {{ number_format($totalProfit, 2) }}</div>
                <div class="mt-3 text-xs text-gold-400">Based on current resonance</div>
            </div>

            <div class="stat-card p-5 group hover:scale-105 transition-all">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-gold-400 text-sm uppercase tracking-wider">Active Holdings</span>
                    <i class="fas fa-chart-pie text-2xl text-gold opacity-60 group-hover:opacity-100 transition group-hover:rotate-12"></i>
                </div>
                <div class="stat-value text-3xl font-bold">{{ $activeInvestments }}</div>
                <div class="mt-2 flex justify-between items-center">
                    <span class="text-xs text-gold-500">Completed: {{ $completedInvestments }}</span>
                    <a href="{{ route('investments.index') }}" class="text-xs text-gold-400 hover:text-gold transition">View All →</a>
                </div>
            </div>
        </div>

        {{-- ==================== QUICK NAVIGATION (11 PORTALS) ==================== --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-11 gap-3 mb-10">
            <a href="{{ route('dashboard') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fas fa-home text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Home</p></a>
            <a href="{{ route('machines.index') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fas fa-microchip text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Machines</p></a>
            <a href="{{ route('trading.index') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fab fa-bitcoin text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Trade</p></a>
            <a href="{{ route('lottery.index') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fas fa-dice-d6 text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Lottery</p></a>
            <a href="{{ route('loans.index') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fas fa-hand-holding-usd text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Loans</p></a>
            <a href="{{ route('social-trading.leaderboard') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fas fa-trophy text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Leaderboard</p></a>
            <a href="{{ route('wallet') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fas fa-wallet text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Wallet</p></a>
            <a href="{{ route('deposit.form') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fas fa-arrow-down text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Deposit</p></a>
            <a href="{{ route('withdrawal.form') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fas fa-arrow-up text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Withdraw</p></a>
            <a href="{{ route('profile.edit') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group"><i class="fas fa-user-circle text-2xl text-gold group-hover:scale-110 transition"></i><p class="text-xs text-ivory/70 mt-1">Profile</p></a>
            <a href="{{ route('notifications.index') }}" class="quick-nav-item bg-gold/10 rounded-xl p-3 text-center hover:bg-gold/20 transition-all hover:scale-105 group relative"><i class="fas fa-bell text-2xl text-gold group-hover:scale-110 transition"></i><span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 min-w-[20px] animate-pulse" x-show="unreadCount > 0" x-text="unreadCount"></span><p class="text-xs text-ivory/70 mt-1">Alerts</p></a>
        </div>

        {{-- ==================== LOTTERY JACKPOT ==================== --}}
        <div class="card-golden p-5 mb-6 group hover:scale-[1.02] transition-all text-center">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gold-400 text-sm uppercase tracking-wider">🎰 Cosmic Jackpot</p>
                    <p class="text-3xl font-bold text-green-400" id="lotteryJackpot">KES {{ number_format($lotteryJackpot ?? 1000, 2) }}</p>
                </div>
                <a href="{{ route('lottery.index') }}" class="btn-golden">Play Now →</a>
            </div>
            <div class="mt-3 text-xs text-ivory/50">Progressive jackpot – grows with every spin</div>
        </div>

        {{-- ==================== BANKING CENTER ==================== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
            <div class="lg:col-span-2 card-golden p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gold">🏦 Banking Center</h3>
                    <div class="flex gap-3">
                        <a href="{{ route('deposit.form') }}" class="btn-golden text-sm py-2 px-4 hover:scale-105 transition">Deposit</a>
                        <a href="{{ route('withdrawal.form') }}" class="btn-outline-silver text-sm py-2 px-4 rounded-lg">Withdraw</a>
                    </div>
                </div>
                <div class="flex justify-between items-center border-b border-gold/20 pb-4 mb-4">
                    <div>
                        <p class="text-gold-400 text-sm">Available Balance</p>
                        <p class="text-3xl font-bold text-gold shimmer-gold" x-text="'KES ' + formatNumber(walletBalance)">KES 0</p>
                    </div>
                    <i class="fas fa-wallet text-4xl text-gold/50 animate-pulse"></i>
                </div>
                <div class="mt-2">
                    <h4 class="text-sm font-medium text-gold-400 mb-2">Quick Stats</h4>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-gold/5 rounded-lg p-2">
                            <p class="text-xs text-ivory/60">Deposits</p>
                            <p class="text-sm font-bold text-green-400">KES {{ number_format($totalDeposited, 2) }}</p>
                        </div>
                        <div class="bg-gold/5 rounded-lg p-2">
                            <p class="text-xs text-ivory/60">Withdrawals</p>
                            <p class="text-sm font-bold text-red-400">KES {{ number_format($totalWithdrawn, 2) }}</p>
                        </div>
                        <div class="bg-gold/5 rounded-lg p-2">
                            <p class="text-xs text-ivory/60">Interest</p>
                            <p class="text-sm font-bold text-gold">KES {{ number_format($totalInterest, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-golden p-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold text-gold">Recent Activity</h3>
                    <a href="{{ route('transactions.index') }}" class="text-xs text-gold-400 hover:text-gold">View all</a>
                </div>
                <div class="space-y-3 max-h-64 overflow-y-auto custom-scroll">
                    @forelse($recentTransactions as $tx)
                        <div class="flex justify-between items-center text-sm border-b border-gold/20 pb-2 group hover:bg-gold/5 p-2 rounded-lg transition">
                            <div>
                                <span class="text-ivory/70">{{ $tx->created_at->format('d M') }}</span>
                                <span class="ml-2 {{ $tx->amount > 0 ? 'text-green-400' : 'text-red-400' }} font-semibold">
                                    {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount, 2) }}
                                </span>
                            </div>
                            <span class="text-ivory/50 text-xs">{{ ucfirst($tx->type) }}</span>
                        </div>
                    @empty
                        <p class="text-center text-ivory/50 py-4">No recent activity</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ==================== LOANS WIDGET ==================== --}}
        <div class="card-golden p-5 mb-6 group hover:scale-[1.02] transition-all">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gold">💸 Loans & Credit</h3>
                <a href="{{ route('loans.index') }}" class="text-sm text-gold-400 hover:text-gold">Manage →</a>
            </div>

            <div class="mb-4">
                <div class="flex justify-between items-center text-xs text-gold-400 mb-1">
                    <span>Credit Score</span>
                    <span class="font-bold text-gold">{{ $creditScore->score ?? 500 }} · {{ $creditScore->tier ?? 'Silver' }}</span>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-red-400 via-yellow-400 to-green-400 h-2 rounded-full"
                         style="width: {{ min(100, max(0, (($creditScore->score ?? 500) - 300) / 5.5)) }}%"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 text-center">
                <div class="bg-gold/5 rounded-lg p-2">
                    <p class="text-xs text-gold-400">Borrowed</p>
                    <p class="text-sm font-bold text-gold">KES {{ number_format($loanStats['total_borrowed'], 0) }}</p>
                </div>
                <div class="bg-gold/5 rounded-lg p-2">
                    <p class="text-xs text-gold-400">Outstanding</p>
                    <p class="text-sm font-bold text-red-400">KES {{ number_format($loanStats['total_outstanding'], 0) }}</p>
                </div>
                <div class="bg-gold/5 rounded-lg p-2">
                    <p class="text-xs text-gold-400">Active</p>
                    <p class="text-sm font-bold text-gold">{{ $loanStats['active_count'] }}</p>
                </div>
                <div class="bg-gold/5 rounded-lg p-2">
                    <p class="text-xs text-gold-400">Completed</p>
                    <p class="text-sm font-bold text-green-400">{{ $loanStats['completed_count'] }}</p>
                </div>
            </div>

            @if($loanStats['has_overdue'])
                <div class="bg-red-500/20 border border-red-500/40 rounded-lg p-2 mb-4 text-xs text-red-300">
                    ⚠️ You have an overdue repayment. Please settle to protect your credit score.
                </div>
            @endif

            <div>
                <p class="text-xs uppercase text-gold-400 mb-2">Upcoming Repayments</p>
                @forelse($upcomingRepayments as $r)
                    <div class="flex justify-between items-center text-sm border-b border-gold/20 pb-2 mb-2">
                        <div>
                            <p class="text-ivory/80">{{ $r->loan->product_name ?? 'Loan' }}</p>
                            <p class="text-xs text-gold-400/60">Due {{ optional($r->due_date)->format('M d, Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-gold font-bold">KES {{ number_format($r->amount, 2) }}</p>
                            <a href="{{ route('loans.show', $r->loan_id) }}" class="text-xs text-gold-400 hover:text-gold">Repay →</a>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-ivory/50 text-center py-2">No pending repayments</p>
                @endforelse
            </div>

            <div class="mt-4 flex gap-2">
                <a href="{{ route('loans.index') }}" class="btn-golden flex-1 text-center text-sm py-2">Browse Loans</a>
                @if($loanStats['active_count'] > 0 || $loanStats['pending_count'] > 0)
                    <a href="{{ route('loans.index') }}" class="btn-outline-silver flex-1 text-center text-sm py-2">My Loans</a>
                @endif
            </div>
        </div>

        {{-- ==================== REFERRAL WIDGET ==================== --}}
        <div class="card-golden p-5 mb-6 group hover:scale-[1.02] transition-all">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gold">🤝 Referral Program</h3>
                <a href="{{ route('referrals') }}" class="text-sm text-gold-400 hover:text-gold">View all →</a>
            </div>
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gold-400 text-sm">Total Referrals</p>
                    <p class="text-2xl font-bold text-gold">{{ $referralCount }}</p>
                </div>
                <div>
                    <p class="text-gold-400 text-sm">Bonus Earned</p>
                    <p class="text-2xl font-bold text-gold">KES {{ number_format($totalBonus, 2) }}</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gold/20 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                        <i class="fas fa-users text-2xl text-gold"></i>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button onclick="copyReferralLink()" class="btn-golden w-full text-sm py-2 group-hover:scale-[1.02] transition">
                    <i class="fas fa-link mr-2"></i> Share Your Divine Link
                </button>
            </div>
        </div>

        {{-- ==================== TRADING WIDGET ==================== --}}
        <div class="card-golden p-5 mb-6 group hover:scale-[1.02] transition-all">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gold">₿ Bitcoin Trading</h3>
                <a href="{{ route('trading.index') }}" class="text-sm text-gold-400 hover:text-gold">Trade Now →</a>
            </div>
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gold-400 text-sm">Trading Balance</p>
                    <p class="text-2xl font-bold text-gold">KES {{ number_format($user->tradingAccount?->balance ?? 0, 2) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-gold-400 text-sm">BTC Price</p>
                    <p class="text-2xl font-bold text-gold" id="btcPrice">KES {{ number_format($btcPrice, 2) }}</p>
                    <p class="text-xs text-green-400 animate-pulse" id="btcChange">+2.4% (24h)</p>
                </div>
                <i class="fab fa-bitcoin text-4xl text-gold/50 group-hover:scale-110 transition"></i>
            </div>
        </div>

        {{-- ==================== MACHINE CTA ==================== --}}
        <div class="mb-10 mt-6">
            <div class="bg-gradient-to-r from-gold-600/20 via-gold-500/10 to-gold-600/20 rounded-2xl p-6 border border-gold/30 text-center group hover:scale-[1.02] transition-all">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-left">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-microchip text-3xl text-gold group-hover:rotate-12 transition"></i>
                            <h3 class="text-2xl font-bold golden-title">🤖 RX Machine Series</h3>
                            <span class="bg-gold/20 text-gold text-xs px-2 py-1 rounded-full animate-pulse">Φ Golden Ratio</span>
                        </div>
                        <p class="text-ivory/70">Invest in our high‑return machines and earn up to 88% in 14 days</p>
                        <p class="text-sm text-gold-400 mt-1">7 Machines • 21 VIP Portals • 8888 Hz Frequency</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <a href="{{ route('machines.index') }}" class="btn-golden inline-flex items-center gap-2 group-hover:scale-105 transition">
                            Explore Machines <i class="fas fa-arrow-right group-hover:translate-x-1 transition"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== CHARTS ==================== --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
            <div class="card-golden p-5">
                <h3 class="text-lg font-semibold text-gold mb-4">📈 30-Day Profit Trend</h3>
                <canvas id="profitChart" height="200" class="w-full"></canvas>
            </div>
            <div class="card-golden p-5">
                <h3 class="text-lg font-semibold text-gold mb-4">📊 Weekly Activity</h3>
                <canvas id="weeklyChart" height="200" class="w-full"></canvas>
            </div>
        </div>

        {{-- ==================== PORTFOLIO ==================== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
            @if(count($portfolio['labels'] ?? []) > 0)
                <div class="lg:col-span-2 card-golden p-5">
                    <h3 class="text-lg font-semibold text-gold mb-4">🥧 Portfolio Breakdown</h3>
                    <canvas id="portfolioChart" height="200" class="w-full"></canvas>
                </div>
            @endif

            <div class="card-golden p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gold">🪙 Live Crypto Prices</h3>
                    <button onclick="fetchCryptoPrices()" class="text-gold-400 hover:text-gold transition">
                        <i class="fas fa-sync-alt text-sm" id="cryptoSpinner"></i>
                    </button>
                </div>
                <div class="space-y-3" id="cryptoList">
                    <p class="text-ivory/50 text-center py-4">Loading prices...</p>
                </div>
            </div>
        </div>

        {{-- ==================== RECENT TRANSACTIONS TABLE ==================== --}}
        <div class="card-golden p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gold">📋 Recent Transactions</h3>
                <div class="flex gap-3">
                    <button @click="exportTransactions()" class="text-sm text-gold-400 hover:text-gold transition">
                        <i class="fas fa-file-excel mr-1"></i> Export
                    </button>
                    <a href="{{ route('transactions.index') }}" class="text-sm text-gold-400 hover:text-gold transition">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-gold/30">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gold uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gold uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gold uppercase">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gold uppercase">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gold uppercase">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gold/20">
                        @foreach($recentTransactions->take(10) as $tx)
                            <tr class="hover:bg-gold/5 transition cursor-pointer">
                                <td class="px-4 py-3 text-sm text-ivory">{{ $tx->created_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full @if(in_array($tx->type, ['deposit','interest','loan_disbursement'])) bg-green-500/20 text-green-400 @elseif(in_array($tx->type, ['withdrawal','debit','loan_repayment'])) bg-red-500/20 text-red-400 @else bg-blue-500/20 text-blue-400 @endif">
                                        {{ ucfirst($tx->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-ivory/70">{{ $tx->description ?? '---' }}</td>
                                <td class="px-4 py-3 text-sm font-medium {{ $tx->amount > 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-ivory">{{ number_format($tx->balance_after ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center mt-10 pt-6 border-t border-gold/20">
            <p class="text-xs text-gold-400/60 sacred-phrase">
                Divine Eternal Universal Frequencies | Guardian and Protector | 888 Hz
            </p>
            <p class="text-xs text-gold-500/40 mt-1">
                Racksephnox -- Infinite Spiral of Creation | Golden Ratio Φ
            </p>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function unifiedDashboard() {
    return {
        loading: true,
        refreshing: false,
        unreadCount: {{ $unreadNotificationsCount ?? 0 }},
        walletBalance: {{ $walletBalance }},

        async init() {
            setTimeout(() => {
                this.initCharts();
                this.loading = false;
            }, 300);
            setInterval(() => this.refreshUnreadCount(), 30000);
            setInterval(() => this.refreshBTCPrice(), 10000);
            setInterval(() => this.refreshLotteryJackpot(), 15000);
            this.refreshLotteryJackpot();
            fetchCryptoPrices();
        },

        initCharts() {
            const profitCtx = document.getElementById('profitChart')?.getContext('2d');
            if (profitCtx) {
                new Chart(profitCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($profitHistory['labels'] ?? []) !!},
                        datasets: [{
                            label: 'Daily Profit (KES)',
                            data: {!! json_encode($profitHistory['data'] ?? []) !!},
                            borderColor: '#D4AF37',
                            backgroundColor: 'rgba(212, 175, 55, 0.1)',
                            tension: 0.4, fill: true,
                            pointBackgroundColor: '#FFD700',
                            pointBorderColor: '#B8860B',
                            pointRadius: 4,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { labels: { color: '#D4AF37' } } },
                        scales: {
                            y: { grid: { color: 'rgba(212, 175, 55, 0.1)' }, ticks: { color: '#D4AF37' } },
                            x: { ticks: { color: '#D4AF37' } },
                        },
                    },
                });
            }

            const weeklyCtx = document.getElementById('weeklyChart')?.getContext('2d');
            if (weeklyCtx) {
                new Chart(weeklyCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($weeklyPerformance['labels'] ?? []) !!},
                        datasets: [{
                            label: 'Volume (KES)',
                            data: {!! json_encode($weeklyPerformance['data'] ?? []) !!},
                            backgroundColor: '#D4AF37',
                            borderRadius: 8,
                            hoverBackgroundColor: '#FFD700',
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { labels: { color: '#D4AF37' } } },
                        scales: {
                            y: { grid: { color: 'rgba(212, 175, 55, 0.1)' }, ticks: { color: '#D4AF37' } },
                            x: { ticks: { color: '#D4AF37' } },
                        },
                    },
                });
            }

            @if(count($portfolio['labels'] ?? []) > 0)
            const portfolioCtx = document.getElementById('portfolioChart')?.getContext('2d');
            if (portfolioCtx) {
                new Chart(portfolioCtx, {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode($portfolio['labels'] ?? []) !!},
                        datasets: [{
                            data: {!! json_encode($portfolio['data'] ?? []) !!},
                            backgroundColor: ['#FFD700', '#D4AF37', '#B8860B', '#CD7F32', '#C5A028', '#FFC0CB'],
                            borderWidth: 0, hoverOffset: 10,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { color: '#D4AF37' } },
                            tooltip: { callbacks: { label: (ctx) => 'KES ' + ctx.raw.toLocaleString() } },
                        },
                    },
                });
            }
            @endif
        },

        async refreshStats() {
            this.refreshing = true;
            try {
                const res = await fetch('/api/wallet/balance');
                if (res.ok) {
                    const data = await res.json();
                    this.walletBalance = data.balance;
                    this.showToast('Balance refreshed', 'success');
                }
            } catch (e) {
                this.showToast('Failed to refresh', 'error');
            } finally {
                this.refreshing = false;
            }
        },

        async refreshUnreadCount() {
            try {
                const res = await fetch('/api/notifications/unread-count');
                if (res.ok) this.unreadCount = (await res.json()).count;
            } catch (e) { console.error(e); }
        },

        async refreshBTCPrice() {
            try {
                const res = await fetch('/api/trading/price');
                if (res.ok) {
                    const data = await res.json();
                    const el = document.getElementById('btcPrice');
                    if (el) el.innerText = 'KES ' + (data.price_kes || {{ $btcPrice }}).toLocaleString();
                }
            } catch (e) { console.error(e); }
        },

        async refreshLotteryJackpot() {
            try {
                const res = await fetch('/api/lottery/jackpot');
                if (res.ok) {
                    const data = await res.json();
                    const el = document.getElementById('lotteryJackpot');
                    if (el) el.innerText = 'KES ' + (data.jackpot || 1000).toLocaleString();
                }
            } catch (e) { console.error(e); }
        },

        exportTransactions() {
            window.location.href = '{{ route('transactions.export') }}';
            this.showToast('Exporting...', 'info');
        },

        showToast(msg, type) {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 z-50 px-6 py-3 rounded-xl text-white font-semibold shadow-xl transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-gold-500'
            }`;
            toast.innerHTML = `<i class="fas ${
                type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'
            } mr-2"></i>${msg}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        },

        formatNumber(num) {
            if (num >= 1e6) return (num / 1e6).toFixed(2) + 'M';
            if (num >= 1e3) return (num / 1e3).toFixed(2) + 'K';
            return Number(num || 0).toLocaleString();
        },
    };
}

async function fetchCryptoPrices() {
    const spinner = document.getElementById('cryptoSpinner');
    if (spinner) spinner.classList.add('animate-spin');
    const list = document.getElementById('cryptoList');
    try {
        const res = await fetch('/api/crypto-prices');
        if (!res.ok) throw new Error('Failed to load');
        const data = await res.json();
        const coins = Array.isArray(data) ? data : [];
        if (!coins.length) {
            list.innerHTML = '<p class="text-ivory/50 text-center py-4">No data</p>';
            return;
        }
        list.innerHTML = coins.slice(0, 5).map(c => `
            <div class="flex justify-between items-center border-b border-gold/20 pb-2 hover:bg-gold/5 p-2 rounded-lg transition">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-ivory">${c.symbol ?? '—'}</span>
                    <span class="text-xs ${(+c.percent_change_24h || 0) >= 0 ? 'text-green-400' : 'text-red-400'}">
                        ${(+c.percent_change_24h || 0).toFixed(2)}%
                    </span>
                </div>
                <span class="text-gold font-bold">KES ${Number(c.price_kes || 0).toLocaleString()}</span>
            </div>
        `).join('');
    } catch (e) {
        list.innerHTML = '<p class="text-red-400 text-center py-4 text-xs">Failed to load prices</p>';
    } finally {
        if (spinner) spinner.classList.remove('animate-spin');
    }
}

function copyReferralLink() {
    const link = "{{ url('/register?ref=' . (auth()->user()->referral_code ?? '')) }}";
    navigator.clipboard.writeText(link)
        .then(() => alert('✨ Referral link copied!'))
        .catch(() => alert('Failed to copy'));
}
</script>
<style>
@keyframes slide { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
.animate-slide { animation: slide 20s linear infinite; }
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: rgba(212, 175, 55, 0.1); border-radius: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.5); border-radius: 4px; }
[x-cloak] { display: none !important; }
</style>
@endsection
