@extends('admin.layouts.app')

@section('page-title', $user->name)
@section('content')

@php
    $me = auth()->user();
    $isSuper = $me->hasRole('super_admin');
@endphp

<div class="space-y-4">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-gold to-yellow-700 flex items-center justify-center text-black font-bold text-2xl">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $user->name }}</h1>
                <p class="text-sm text-gray-400">{{ $user->email }} @if($user->phone) · {{ $user->phone }} @endif</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    @if($user->is_admin) <span class="px-2 py-0.5 bg-red-500/20 text-red-400 text-xs rounded">{{ $user->role_label }}</span> @endif
                    @if($user->is_vip) <span class="px-2 py-0.5 bg-yellow-500/20 text-yellow-400 text-xs rounded">VIP</span> @endif
                    @if($user->has_promo) <span class="px-2 py-0.5 bg-purple-500/20 text-purple-400 text-xs rounded">Promo</span> @endif
                    <span class="px-2 py-0.5 bg-{{ in_array($user->kyc_status, ['verified','approved']) ? 'green' : 'yellow' }}-500/20 text-{{ in_array($user->kyc_status, ['verified','approved']) ? 'green' : 'yellow' }}-400 text-xs rounded">
                        KYC: {{ ucfirst($user->kyc_status ?? 'pending') }}
                    </span>
                    <span class="px-2 py-0.5 bg-gray-500/20 text-gray-400 text-xs rounded">Ref: {{ $user->referral_code ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            @if($isSuper)
                <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded text-sm">Edit</a>
            @endif
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Back</a>
        </div>
    </div>

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded">{{ $errors->first() }}</div> @endif

    {{-- STATS GRID --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="admin-card p-4">
            <p class="text-xs text-gray-400 uppercase">Wallet Balance</p>
            <p class="text-2xl font-bold text-green-400">KES {{ number_format($stats['wallet_balance'], 2) }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-gray-400 uppercase">Total Invested</p>
            <p class="text-2xl font-bold text-gold">KES {{ number_format($stats['total_invested'], 2) }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-gray-400 uppercase">Trading Balance</p>
            <p class="text-2xl font-bold text-white">${{ number_format($stats['trading_balance'], 2) }}</p>
        </div>
        <div class="admin-card p-4">
            <p class="text-xs text-gray-400 uppercase">Active Loans</p>
            <p class="text-2xl font-bold text-orange-400">{{ $stats['active_loans'] }}</p>
        </div>
    </div>

    {{-- ADMIN ADJUSTMENT HUB --}}
    @if($isSuper)
    <div class="admin-card p-5 border-l-4 border-l-red-500" x-data="{ tab: 'wallet' }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-red-400">🔧 Admin Adjustment Hub</h3>
            <span class="text-xs text-red-400/70">⚠️ Every action is logged</span>
        </div>

        <div class="flex flex-wrap gap-2 mb-4 border-b border-gray-800 pb-2">
            <button @click="tab='wallet'" :class="tab==='wallet' ? 'bg-gold text-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'" class="px-3 py-1 text-xs font-bold rounded transition">💰 Wallet</button>
            <button @click="tab='machine'" :class="tab==='machine' ? 'bg-gold text-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'" class="px-3 py-1 text-xs font-bold rounded transition">🏭 Machines</button>
            <button @click="tab='trading'" :class="tab==='trading' ? 'bg-gold text-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'" class="px-3 py-1 text-xs font-bold rounded transition">📈 Trading</button>
            <button @click="tab='loan'" :class="tab==='loan' ? 'bg-gold text-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'" class="px-3 py-1 text-xs font-bold rounded transition">💵 Loans</button>
            <button @click="tab='lottery'" :class="tab==='lottery' ? 'bg-gold text-black' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'" class="px-3 py-1 text-xs font-bold rounded transition">🎰 Lottery</button>
        </div>

        {{-- Wallet --}}
        <div x-show="tab==='wallet'" x-cloak>
            <form method="POST" action="{{ route('admin.users.adjust.wallet', $user) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Type</label>
                        <select name="type" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required>
                            <option value="credit">Credit (+)</option>
                            <option value="debit">Debit (−)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Amount (KES)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required>
                    </div>
                </div>
                <input type="text" name="reason" minlength="10" required placeholder="Reason (min 10 chars)" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm">
                <button type="submit" onclick="return confirm('Execute wallet adjustment?')" class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-2 rounded transition">Execute Wallet Adjustment</button>
            </form>
        </div>

        {{-- Machine --}}
        <div x-show="tab==='machine'" x-cloak class="space-y-4">
            <form method="POST" action="{{ route('admin.users.adjust.machine', $user) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Machine</label>
                        <select name="machine_id" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required>
                            <option value="">Select machine…</option>
                            @foreach(\App\Models\Machine::where('is_active', true)->orderBy('sort_order')->get() as $m)
                                <option value="{{ $m->id }}">{{ $m->code }} · {{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Amount (KES)</label>
                        <input type="number" step="0.01" min="1" name="amount" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">VIP Level</label>
                        <select name="vip_level" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm">
                            <option value="0">Base</option>
                            <option value="1">VIP 1</option>
                            <option value="2">VIP 2</option>
                            <option value="3">VIP 3</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Fund From</label>
                        <select name="fund_from" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required>
                            <option value="admin">Admin (free grant)</option>
                            <option value="wallet">User's wallet</option>
                        </select>
                    </div>
                </div>
                <input type="text" name="reason" minlength="10" required placeholder="Reason (min 10 chars)" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm">
                <button type="submit" onclick="return confirm('Create this machine investment?')" class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-2 rounded transition">Create Investment</button>
            </form>

            @if($user->machineInvestments->where('status', 'active')->count() > 0)
            <div class="border-t border-gray-800 pt-3">
                <p class="text-xs text-gray-400 mb-2">Active investments (click cancel to refund)</p>
                <div class="space-y-2">
                    @foreach($user->machineInvestments->where('status', 'active') as $inv)
                        <div class="flex items-center justify-between bg-gray-800/50 p-2 rounded text-sm">
                            <div>
                                <span class="text-white font-medium">{{ $inv->machine->name ?? 'Machine' }}</span>
                                <span class="text-gray-400 ml-2">KES {{ number_format($inv->amount, 2) }}</span>
                            </div>
                            <form method="POST" action="{{ route('admin.users.adjust.machine.cancel', [$user, $inv->id]) }}" class="flex gap-1 items-center">
                                @csrf
                                <input type="number" name="refund_amount" step="0.01" min="0" value="{{ $inv->amount }}" class="w-24 bg-gray-900 border border-gray-700 text-white rounded px-2 py-1 text-xs">
                                <input type="text" name="reason" minlength="10" required placeholder="reason" class="w-32 bg-gray-900 border border-gray-700 text-white rounded px-2 py-1 text-xs">
                                <button onclick="return confirm('Cancel this investment?')" class="bg-orange-600 hover:bg-orange-700 text-white text-xs px-2 py-1 rounded">Cancel</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Trading --}}
        <div x-show="tab==='trading'" x-cloak>
            <form method="POST" action="{{ route('admin.users.adjust.trading', $user) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Type</label>
                        <select name="type" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required>
                            <option value="credit">Credit (+)</option>
                            <option value="debit">Debit (−)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Amount ($ USDT)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required>
                    </div>
                </div>
                <input type="text" name="reason" minlength="10" required placeholder="Reason (min 10 chars)" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm">
                <button type="submit" onclick="return confirm('Execute trading adjustment?')" class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-2 rounded transition">Execute Trading Adjustment</button>
            </form>
        </div>

        {{-- Loans --}}
        <div x-show="tab==='loan'" x-cloak class="space-y-4">
            @forelse($user->loans as $loan)
                <div class="bg-gray-800/50 p-3 rounded">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <span class="text-white font-medium">Loan #{{ $loan->id }}</span>
                            <span class="text-gray-400 ml-2">KES {{ number_format($loan->principal ?? 0, 2) }}</span>
                            <span class="px-2 py-0.5 ml-2 text-xs rounded {{ $loan->status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-700 text-gray-300' }}">{{ ucfirst($loan->status) }}</span>
                        </div>
                        <span class="text-xs text-gray-500">Balance: KES {{ number_format($loan->balance ?? 0, 2) }}</span>
                    </div>
                    @if(in_array($loan->status, ['active', 'pending']))
                    <form method="POST" action="{{ route('admin.users.adjust.loan', $user) }}" class="grid grid-cols-5 gap-2">
                        @csrf
                        <input type="hidden" name="loan_id" value="{{ $loan->id }}">
                        <select name="action" class="bg-gray-900 border border-gray-700 text-white rounded px-2 py-1 text-xs">
                            <option value="forgive">Forgive</option>
                            <option value="mark_paid">Paid</option>
                            <option value="mark_defaulted">Default</option>
                            <option value="adjust_balance">Reduce</option>
                        </select>
                        <input type="number" step="0.01" min="0" name="amount" placeholder="amount" class="bg-gray-900 border border-gray-700 text-white rounded px-2 py-1 text-xs">
                        <input type="text" name="reason" minlength="10" required placeholder="reason" class="col-span-2 bg-gray-900 border border-gray-700 text-white rounded px-2 py-1 text-xs">
                        <button onclick="return confirm('Apply loan adjustment?')" class="bg-red-600 hover:bg-red-700 text-white text-xs rounded">Execute</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">No loans on this account.</p>
            @endforelse
        </div>

        {{-- Lottery --}}
        <div x-show="tab==='lottery'" x-cloak class="space-y-4">
            <form method="POST" action="{{ route('admin.users.adjust.lottery', $user) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Action</label>
                    <select name="action" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm" required>
                        <option value="grant_spins">Grant Free Spins</option>
                        <option value="grant_win">Grant Cash Win</option>
                        <option value="reset_stats">Reset Stats (Dangerous)</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Spins</label>
                        <input type="number" min="1" max="100" name="spins" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Win Amount KES</label>
                        <input type="number" step="0.01" min="1" name="win_amount" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm">
                    </div>
                </div>
                <input type="text" name="reason" minlength="10" required placeholder="Reason (min 10 chars)" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 text-sm">
                <button type="submit" onclick="return confirm('Apply lottery adjustment?')" class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-4 py-2 rounded transition">Execute Lottery Adjustment</button>
            </form>
        </div>
    </div>
    @endif

    {{-- RECENT TRANSACTIONS --}}
    <div class="admin-card p-5">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-semibold text-gold">📋 Recent Transactions</h3>
            <span class="text-xs text-gray-400">{{ $stats['transaction_count'] }} total</span>
        </div>
        @if($user->transactions->count() > 0)
            <table class="w-full text-sm">
                <thead class="border-b border-gray-800 text-gray-400 text-xs uppercase">
                    <tr><th class="text-left p-2">Date</th><th class="text-left p-2">Type</th><th class="text-left p-2">Description</th><th class="text-right p-2">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach($user->transactions as $tx)
                        <tr class="border-b border-gray-800/50">
                            <td class="p-2 text-xs text-gray-500">{{ $tx->created_at?->format('M d, H:i') }}</td>
                            <td class="p-2 text-xs text-gold">{{ ucfirst($tx->type ?? '—') }}</td>
                            <td class="p-2 text-xs text-gray-400">{{ Str::limit($tx->description ?? '—', 60) }}</td>
                            <td class="p-2 text-right text-xs">KES {{ number_format($tx->amount ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-gray-500">No transactions yet.</p>
        @endif
    </div>

</div>
@endsection
