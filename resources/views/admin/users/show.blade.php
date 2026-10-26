@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">

    {{-- ==================== HEADER ==================== --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-gold to-yellow-700 flex items-center justify-center text-black font-bold text-2xl">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $user->name }}</h1>
                <p class="text-sm text-gray-400">{{ $user->email }} @if($user->phone) · {{ $user->phone }} @endif</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    @if($user->is_admin) <span class="px-2 py-0.5 bg-red-500/20 text-red-400 text-xs rounded">Admin</span> @endif
                    @if($user->is_vip) <span class="px-2 py-0.5 bg-yellow-500/20 text-yellow-400 text-xs rounded">VIP</span> @endif
                    @if($user->has_promo) <span class="px-2 py-0.5 bg-purple-500/20 text-purple-400 text-xs rounded">Promo</span> @endif
                    <span class="px-2 py-0.5 bg-{{ $user->kyc_status === 'verified' || $user->kyc_status === 'approved' ? 'green' : 'yellow' }}-500/20 text-{{ $user->kyc_status === 'verified' || $user->kyc_status === 'approved' ? 'green' : 'yellow' }}-400 text-xs rounded">
                        KYC: {{ ucfirst($user->kyc_status ?? 'pending') }}
                    </span>
                    <span class="px-2 py-0.5 bg-gray-500/20 text-gray-400 text-xs rounded">Ref: {{ $user->referral_code ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">Edit</a>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Back</a>
        </div>
    </div>

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded">{{ $errors->first() }}</div> @endif

    {{-- ==================== STATS GRID ==================== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="admin-card p-4">
            <p class="text-xs text-gray-400 uppercase">Wallet Balance</p>
            <p class="text-2xl font-bold text-green-400">KES {{ number_format($stats['wallet_balance'], 2) }}</p>
            @if($stats['wallet_locked'] > 0)
                <p class="text-xs text-orange-400">Locked: KES {{ number_format($stats['wallet_locked'], 2) }}</p>
            @endif
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-gray-400 uppercase">Total Deposited</p>
            <p class="text-2xl font-bold text-gold">KES {{ number_format($stats['total_deposited'], 2) }}</p>
            @if($stats['pending_deposits'] > 0)
                <p class="text-xs text-yellow-400">{{ $stats['pending_deposits'] }} pending</p>
            @endif
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-gray-400 uppercase">Total Withdrawn</p>
            <p class="text-2xl font-bold text-orange-400">KES {{ number_format($stats['total_withdrawn'], 2) }}</p>
            @if($stats['pending_withdrawals'] > 0)
                <p class="text-xs text-yellow-400">{{ $stats['pending_withdrawals'] }} pending</p>
            @endif
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-gray-400 uppercase">Total Invested</p>
            <p class="text-2xl font-bold text-gold">KES {{ number_format($stats['total_invested'], 2) }}</p>
            <p class="text-xs text-gray-500">{{ $stats['active_machines'] }} active machine(s)</p>
        </div>
    </div>

    {{-- ==================== TWO COLUMN: WALLET ADJUSTMENT + TRADING ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Manual Wallet Adjustment --}}
        <div class="admin-card p-6 border-t-4 border-t-red-500">
            <h3 class="text-lg font-semibold text-red-400 mb-2">⚠️ Manual Wallet Adjustment</h3>
            <p class="text-sm text-gray-400 mb-4">All adjustments are logged and reversible only via another adjustment.</p>
            <form method="POST" action="{{ route('admin.users.adjust-wallet', $user) }}">
                @csrf
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Type</label>
                        <select name="type" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required>
                            <option value="credit">Credit (+)</option>
                            <option value="debit">Debit (−)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Amount (KES)</label>
                        <input type="number" step="0.01" min="1" name="amount" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required placeholder="500">
                    </div>
                </div>
                <label class="block text-xs text-gray-400 mb-1">Reason (audit log)</label>
                <input type="text" name="reason" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm mb-3" required placeholder="e.g. Failed M-Pesa callback fix">
                <button class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded text-sm transition">Execute Adjustment</button>
            </form>
        </div>

        {{-- Trading Account --}}
        <div class="admin-card p-6">
            <h3 class="text-lg font-semibold text-gold mb-4">📈 Trading Account</h3>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-xs text-gray-400">Balance</p>
                    <p class="text-xl font-bold text-white">${{ number_format($stats['trading_balance'], 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Total P&L</p>
                    <p class="text-xl font-bold {{ $stats['trading_pnl'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                        {{ $stats['trading_pnl'] >= 0 ? '+' : '' }}${{ number_format($stats['trading_pnl'], 2) }}
                    </p>
                </div>
            </div>
            @if($stats['open_orders'] > 0)
                <p class="text-sm text-yellow-400">{{ $stats['open_orders'] }} open order(s)</p>
            @endif
            @if($user->tradeOrders->count() > 0)
                <div class="mt-4 border-t border-gray-800 pt-3">
                    <p class="text-xs text-gray-400 mb-2">Recent Orders</p>
                    <div class="space-y-1">
                        @foreach($user->tradeOrders->take(5) as $order)
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400">{{ $order->symbol ?? 'BTCUSDT' }} · {{ $order->side ?? 'buy' }}</span>
                                <span class="{{ ($order->status ?? '') === 'filled' ? 'text-green-400' : 'text-gray-500' }}">{{ ucfirst($order->status ?? 'open') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500 mt-2">No trading activity yet.</p>
            @endif
        </div>
    </div>

    {{-- ==================== MACHINE INVESTMENTS ==================== --}}
    <div class="admin-card p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gold">🏭 Machine Investments</h3>
            <span class="text-sm text-gray-400">{{ $user->machineInvestments->count() }} total · {{ $stats['active_machines'] }} active</span>
        </div>
        @if($user->machineInvestments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-800 text-gray-400 text-xs uppercase">
                        <tr>
                            <th class="text-left p-2">Machine</th>
                            <th class="text-right p-2">Amount</th>
                            <th class="text-right p-2">Returns</th>
                            <th class="text-left p-2">Status</th>
                            <th class="text-right p-2">Started</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->machineInvestments as $inv)
                            <tr class="border-b border-gray-800/50 hover:bg-gray-800/30">
                                <td class="p-2">{{ $inv->machine->name ?? $inv->machine->code ?? 'Unknown' }}</td>
                                <td class="p-2 text-right text-gold">KES {{ number_format($inv->amount ?? 0, 2) }}</td>
                                <td class="p-2 text-right text-green-400">KES {{ number_format($inv->total_returns ?? $inv->earned ?? 0, 2) }}</td>
                                <td class="p-2">
                                    <span class="px-2 py-0.5 text-xs rounded {{ ($inv->status ?? '') === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400' }}">
                                        {{ ucfirst($inv->status ?? 'unknown') }}
                                    </span>
                                </td>
                                <td class="p-2 text-right text-xs text-gray-500">{{ $inv->created_at?->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No machine investments yet.</p>
        @endif
    </div>

    {{-- ==================== DEPOSITS + WITHDRAWALS (side by side) ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="admin-card p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gold">💰 Recent Deposits</h3>
                <a href="{{ route('admin.deposits.index') }}" class="text-xs text-gold-400 hover:text-gold">View All →</a>
            </div>
            @if($user->depositRequests->count() > 0)
                <div class="space-y-2">
                    @foreach($user->depositRequests->take(5) as $d)
                        <div class="flex justify-between items-center py-2 border-b border-gray-800/50">
                            <div>
                                <p class="text-sm text-white">KES {{ number_format($d->amount, 2) }}</p>
                                <p class="text-xs text-gray-500">{{ $d->created_at?->diffForHumans() }}</p>
                            </div>
                            <span class="px-2 py-0.5 text-xs rounded {{ $d->status === 'approved' || $d->status === 'completed' ? 'bg-green-500/20 text-green-400' : ($d->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400') }}">
                                {{ ucfirst($d->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No deposits yet.</p>
            @endif
        </div>

        <div class="admin-card p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gold">🏦 Recent Withdrawals</h3>
                <a href="{{ route('admin.withdrawals.index') }}" class="text-xs text-gold-400 hover:text-gold">View All →</a>
            </div>
            @if($user->withdrawalRequests->count() > 0)
                <div class="space-y-2">
                    @foreach($user->withdrawalRequests->take(5) as $w)
                        <div class="flex justify-between items-center py-2 border-b border-gray-800/50">
                            <div>
                                <p class="text-sm text-white">KES {{ number_format($w->amount, 2) }}</p>
                                <p class="text-xs text-gray-500">{{ $w->created_at?->diffForHumans() }}</p>
                            </div>
                            <span class="px-2 py-0.5 text-xs rounded {{ $w->status === 'completed' ? 'bg-green-500/20 text-green-400' : ($w->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400') }}">
                                {{ ucfirst($w->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No withdrawals yet.</p>
            @endif
        </div>
    </div>

    {{-- ==================== TRANSACTIONS ==================== --}}
    <div class="admin-card p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gold">📋 Recent Transactions</h3>
            <span class="text-xs text-gray-400">{{ $stats['transaction_count'] }} total</span>
        </div>
        @if($user->transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-800 text-gray-400 text-xs uppercase">
                        <tr>
                            <th class="text-left p-2">Date</th>
                            <th class="text-left p-2">Type</th>
                            <th class="text-left p-2">Description</th>
                            <th class="text-right p-2">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->transactions as $tx)
                            <tr class="border-b border-gray-800/50">
                                <td class="p-2 text-xs text-gray-500">{{ $tx->created_at?->format('M d, H:i') }}</td>
                                <td class="p-2 text-xs"><span class="text-gold">{{ ucfirst($tx->type ?? '—') }}</span></td>
                                <td class="p-2 text-xs text-gray-400">{{ Str::limit($tx->description ?? '—', 60) }}</td>
                                <td class="p-2 text-right text-xs">KES {{ number_format($tx->amount ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No transactions yet.</p>
        @endif
    </div>

    {{-- ==================== LOANS + LOTTERY + REFERRALS (3 columns) ==================== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- Loans --}}
        <div class="admin-card p-6">
            <h3 class="text-lg font-semibold text-gold mb-3">💵 Loans</h3>
            <div class="space-y-2 mb-3">
                <p class="text-sm"><span class="text-gray-400">Principal:</span> <span class="text-white">KES {{ number_format($stats['total_loans_principal'], 2) }}</span></p>
                <p class="text-sm"><span class="text-gray-400">Active:</span> <span class="text-white">{{ $stats['active_loans'] }}</span></p>
                @if($stats['credit_score'] !== null)
                    <p class="text-sm"><span class="text-gray-400">Credit Score:</span> <span class="text-gold font-bold">{{ $stats['credit_score'] }}</span></p>
                @endif
            </div>
            @if($user->loans->count() > 0)
                <a href="{{ route('admin.loans.index') }}" class="text-xs text-gold-400 hover:text-gold">View Loans →</a>
            @else
                <p class="text-xs text-gray-500">No loans yet.</p>
            @endif
        </div>

        {{-- Lottery --}}
        <div class="admin-card p-6">
            <h3 class="text-lg font-semibold text-gold mb-3">🎰 Lottery</h3>
            <div class="space-y-2">
                <p class="text-sm"><span class="text-gray-400">Total Bets:</span> <span class="text-white">KES {{ number_format($stats['total_lottery_bets'], 2) }}</span></p>
                <p class="text-sm"><span class="text-gray-400">Total Wins:</span> <span class="text-green-400">KES {{ number_format($stats['total_lottery_wins'], 2) }}</span></p>
                <p class="text-sm"><span class="text-gray-400">Spins:</span> <span class="text-white">{{ $user->lotterySpins->count() }}</span></p>
            </div>
        </div>

        {{-- Referrals --}}
        <div class="admin-card p-6">
            <h3 class="text-lg font-semibold text-gold mb-3">👥 Referrals</h3>
            <p class="text-2xl font-bold text-white mb-2">{{ $stats['total_referrals'] }}</p>
            <p class="text-xs text-gray-400 mb-3">users referred</p>
            @if($user->referrals->count() > 0)
                <div class="space-y-1 border-t border-gray-800 pt-2">
                    @foreach($user->referrals->take(3) as $ref)
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">{{ Str::limit($ref->name, 20) }}</span>
                            <span class="text-gray-500">{{ $ref->created_at?->format('M d') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ==================== BANK ACCOUNTS ==================== --}}
    @if($user->bankAccounts->count() > 0)
        <div class="admin-card p-6">
            <h3 class="text-lg font-semibold text-gold mb-3">🏛️ Linked Bank Accounts</h3>
            <div class="space-y-2">
                @foreach($user->bankAccounts as $acc)
                    <div class="flex justify-between items-center py-2 border-b border-gray-800/50">
                        <div>
                            <p class="text-sm text-white">{{ $acc->bank_name ?? 'Bank' }}</p>
                            <p class="text-xs text-gray-500">{{ $acc->account_number ?? '—' }} · {{ $acc->account_name ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
