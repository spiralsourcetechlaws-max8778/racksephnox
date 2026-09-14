@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">

        <a href="{{ route('loans.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> All Loans
        </a>

        <div class="card-golden p-6 mt-4 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold golden-title">{{ $loan->product_name }}</h1>
                    <p class="text-gold-400 text-xs">{{ $loan->reference }} · {{ $loan->created_at->format('M d, Y') }}</p>
                </div>
                <span class="text-sm uppercase {{ $loan->status_color }} font-bold">{{ $loan->status }}</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-gold/5 rounded p-3">
                    <p class="text-xs text-gold-400">Principal</p>
                    <p class="text-lg font-bold text-gold">KES {{ number_format($loan->principal, 2) }}</p>
                </div>
                <div class="bg-gold/5 rounded p-3">
                    <p class="text-xs text-gold-400">Total Payable</p>
                    <p class="text-lg font-bold text-gold">KES {{ number_format($loan->total_payable, 2) }}</p>
                </div>
                <div class="bg-gold/5 rounded p-3">
                    <p class="text-xs text-gold-400">Paid</p>
                    <p class="text-lg font-bold text-green-400">KES {{ number_format($loan->amount_paid, 2) }}</p>
                </div>
                <div class="bg-gold/5 rounded p-3">
                    <p class="text-xs text-gold-400">Balance</p>
                    <p class="text-lg font-bold text-red-400">KES {{ number_format($loan->balance, 2) }}</p>
                </div>
            </div>

            <div class="mt-6">
                <p class="text-xs text-gold-400 mb-1">Repayment Progress · {{ $loan->progress_percent }}%</p>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-gold-400 to-green-400 h-2 rounded-full"
                         style="width: {{ $loan->progress_percent }}%"></div>
                </div>
            </div>
        </div>

        {{-- Repayment --}}
        @if($loan->can_be_repaid)
            <div class="card-golden p-5 mb-6">
                <h3 class="text-lg font-bold text-gold mb-3">Make a Repayment</h3>
                <form method="POST" action="{{ route('loans.repay', $loan) }}" class="flex gap-3">
                    @csrf
                    <input type="number" name="amount" class="input-golden flex-1" step="0.01"
                           min="1" max="{{ $loan->balance }}" placeholder="Amount (KES)" required>
                    <button type="submit" class="btn-golden px-6">Repay</button>
                </form>
                @if($errors->any())
                    <p class="text-red-400 text-sm mt-2">{{ $errors->first() }}</p>
                @endif
            </div>
        @endif

        {{-- Schedule --}}
        <h3 class="text-lg font-bold text-gold mb-3">Repayment Schedule</h3>
        <div class="card-golden p-4 mb-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gold/30 text-gold-400 text-xs uppercase">
                    <tr>
                        <th class="text-left p-2">#</th>
                        <th class="text-left p-2">Due Date</th>
                        <th class="text-right p-2">Amount</th>
                        <th class="text-right p-2">Principal</th>
                        <th class="text-right p-2">Interest</th>
                        <th class="text-left p-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->repayments as $i => $r)
                        <tr class="border-b border-gold/10">
                            <td class="p-2">{{ $i + 1 }}</td>
                            <td class="p-2 text-ivory/70">{{ optional($r->due_date)->format('M d, Y') }}</td>
                            <td class="p-2 text-right font-bold text-gold">{{ number_format($r->amount, 2) }}</td>
                            <td class="p-2 text-right text-ivory/70">{{ number_format($r->principal_portion, 2) }}</td>
                            <td class="p-2 text-right text-ivory/70">{{ number_format($r->interest_portion, 2) }}</td>
                            <td class="p-2">
                                <span class="{{ $r->status === 'paid' ? 'text-green-400' : 'text-yellow-400' }}">
                                    {{ ucfirst($r->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-4 text-center text-ivory/50">Schedule pending approval.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(in_array($loan->status, ['pending','approved']))
            <form method="POST" action="{{ route('loans.cancel', $loan) }}">
                @csrf
                <button class="text-red-400 text-sm hover:text-red-300"
                        onclick="return confirm('Cancel this loan application?')">
                    Cancel Application
                </button>
            </form>
        @endif

    </div>
</div>
@endsection
