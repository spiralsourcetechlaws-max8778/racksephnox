@extends('layouts.app')

@section('content')
<div x-data="walletManager()" x-init="init()" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 rounded-lg border border-gold-400/40 bg-gradient-to-br from-gold-500/10 to-amber-500/5 p-5">
            <p class="text-xs uppercase tracking-wider text-gold-300 mb-2">Deposit via M-Pesa</p>
            <p class="text-xs text-ivory/50 mb-1">Pochi la Biashara</p>
            <p class="font-mono text-xl font-bold text-gold-200 select-all mb-3">{{ config('mpesa.pochi_number') }}</p>
            <p class="text-xs text-ivory/50 mb-1">Paybill &middot; Account</p>
            <p class="text-base font-semibold text-gold-200"><span class="font-mono select-all">{{ config('mpesa.paybill_number') }}</span> &middot; <span class="font-mono select-all">{{ config('mpesa.paybill_account') }}</span></p>
            <p class="text-xs text-ivory/60 mt-2">{{ config('mpesa.pochi_name') }}</p>
        </div>

        
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold golden-title">Banking Center</h1>
            <p class="text-gold-400 mt-2">Manage your funds, view transactions, and transfer between accounts</p>
        </div>
        
        <!-- Balance Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <div class="card-golden p-6 group hover:shadow-gold transition-all">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gold-400 text-sm uppercase tracking-wider">Main Wallet</p>
                        <p class="text-3xl font-bold text-gold" x-data="{}" x-init="$watch('walletBalance', value => { $el.innerText = 'KES ' + value.toLocaleString() })">
                            KES {{ number_format($wallet->balance, 2) }}
                        </p>
                    </div>
                    <i class="fas fa-wallet text-4xl text-gold/50 group-hover:text-gold transition"></i>
                </div>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('mpesa.deposit') }}" class="btn-golden text-sm py-2 px-4">Deposit</a>
                    <a href="{{ route('mpesa.withdraw') }}" class="btn-golden text-sm py-2 px-4 bg-transparent border border-gold text-gold">Withdraw</a>
                </div>
            </div>
            <div class="card-golden p-6 group hover:shadow-gold transition-all">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gold-400 text-sm uppercase tracking-wider">Trading Account</p>
                        <p class="text-3xl font-bold text-gold">KES {{ number_format($tradingAccount->balance, 2) }}</p>
                    </div>
                    <i class="fas fa-chart-line text-4xl text-gold/50 group-hover:text-gold transition"></i>
                </div>
                <div class="mt-4">
                    <a href="{{ route('trading.index') }}" class="btn-golden text-sm py-2 px-4">Go to Trading →</a>
                </div>
            </div>
        </div>
        
        <!-- Transfer Between Accounts -->
        <div class="card-golden p-6 mb-10">
            <h2 class="text-xl font-bold text-gold mb-4">Transfer Funds</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <form action="{{ route('trading.transfer') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="hidden" name="direction" value="to_trading">
                        <input type="number" name="amount" placeholder="Amount (KES)" class="input-golden flex-1" required>
                        <button type="submit" class="btn-golden">Wallet → Trading</button>
                    </form>
                </div>
                <div>
                    <form action="{{ route('trading.transfer') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="hidden" name="direction" value="to_wallet">
                        <input type="number" name="amount" placeholder="Amount (KES)" class="input-golden flex-1" required>
                        <button type="submit" class="btn-golden">Trading → Wallet</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Transaction Tabs -->
        <div class="card-golden p-6">
            <div class="border-b border-gold/30 mb-4">
                <nav class="flex flex-wrap gap-2">
                    <button @click="activeTab = 'all'" :class="{'border-gold text-gold': activeTab === 'all'}" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent hover:text-gold">All</button>
                    <button @click="activeTab = 'deposits'" :class="{'border-gold text-gold': activeTab === 'deposits'}" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent hover:text-gold">Deposits</button>
                    <button @click="activeTab = 'withdrawals'" :class="{'border-gold text-gold': activeTab === 'withdrawals'}" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent hover:text-gold">Withdrawals</button>
                    <button @click="activeTab = 'interest'" :class="{'border-gold text-gold': activeTab === 'interest'}" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent hover:text-gold">Interest</button>
                    <button @click="activeTab = 'referrals'" :class="{'border-gold text-gold': activeTab === 'referrals'}" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent hover:text-gold">Referral Bonuses</button>
                    <button @click="activeTab = 'trading'" :class="{'border-gold text-gold': activeTab === 'trading'}" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent hover:text-gold">Trading Transfers</button>
                </nav>
            </div>
            
            <!-- All Transactions -->
            <div x-show="activeTab === 'all'">
                @include('wallet.transactions-table', ['transactions' => $transactions])
            </div>
            <div x-show="activeTab === 'deposits'">
                @include('wallet.transactions-table', ['transactions' => $deposits])
            </div>
            <div x-show="activeTab === 'withdrawals'">
                @include('wallet.transactions-table', ['transactions' => $withdrawals])
            </div>
            <div x-show="activeTab === 'interest'">
                @include('wallet.transactions-table', ['transactions' => $interest])
            </div>
            <div x-show="activeTab === 'referrals'">
                @include('wallet.transactions-table', ['transactions' => $referralBonuses])
            </div>
            <div x-show="activeTab === 'trading'">
                @include('wallet.transactions-table', ['transactions' => $tradingTransfers])
            </div>
        </div>
        
        <!-- Stats Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-10">
            <div class="card-golden p-4 text-center">
                <p class="text-gold-400 text-xs">Total Deposits</p>
                <p class="text-xl font-bold text-gold">KES {{ number_format($totalDeposits, 2) }}</p>
            </div>
            <div class="card-golden p-4 text-center">
                <p class="text-gold-400 text-xs">Total Withdrawals</p>
                <p class="text-xl font-bold text-red-400">KES {{ number_format($totalWithdrawals, 2) }}</p>
            </div>
            <div class="card-golden p-4 text-center">
                <p class="text-gold-400 text-xs">Interest Earned</p>
                <p class="text-xl font-bold text-green-400">KES {{ number_format($totalInterest, 2) }}</p>
            </div>
            <div class="card-golden p-4 text-center">
                <p class="text-gold-400 text-xs">Referral Bonus</p>
                <p class="text-xl font-bold text-gold">KES {{ number_format($totalBonus, 2) }}</p>
            </div>
        </div>
        
    </div>
</div>

<script>
function walletManager() {
    return {
        activeTab: 'all',
        walletBalance: {{ $wallet->balance }},
        init() {
            // Periodically refresh wallet balance
            setInterval(() => this.refreshBalance(), 30000);
        },
        async refreshBalance() {
            try {
                const response = await fetch('/api/wallet', {
                    headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
                });
                const data = await response.json();
                this.walletBalance = data.balance;
            } catch (error) {
                console.error('Failed to refresh balance', error);
            }
        }
    }
}
</script>
@endsection
