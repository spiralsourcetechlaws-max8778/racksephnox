@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">
    <a href="{{ route('admin.loans.index') }}" class="text-gold-400 text-sm">← Back to Loans</a>

    <div class="admin-card p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gold">{{ $loan->reference }}</h1>
                <p class="text-sm text-ivory/60">{{ $loan->user->name }} · {{ $loan->user->email }}</p>
            </div>
            <span class="uppercase {{ $loan->status_color }} font-bold">{{ $loan->status }}</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gold/5 rounded p-3">
                <p class="text-xs text-gold-400">Principal</p>
                <p class="text-lg font-bold">KES {{ number_format($loan->principal, 2) }}</p>
            </div>
            <div class="bg-gold/5 rounded p-3">
                <p class="text-xs text-gold-400">Total Payable</p>
                <p class="text-lg font-bold">KES {{ number_format($loan->total_payable, 2) }}</p>
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

        <div class="flex gap-3 flex-wrap">
            @if($loan->status === 'pending')
                <form method="POST" action="{{ route('admin.loans.approve', $loan) }}">
                    @csrf
                    <button class="btn-golden px-4">✅ Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.loans.reject', $loan) }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="reason" placeholder="Rejection reason" class="input-golden" required>
                    <button class="btn-outline-silver px-4">❌ Reject</button>
                </form>
            @endif

            @if($loan->status === 'approved')
                <form method="POST" action="{{ route('admin.loans.disburse', $loan) }}">
                    @csrf
                    <button class="btn-golden px-4">💰 Disburse</button>
                </form>
            @endif
        </div>
    </div>

    <div class="admin-card p-4">
        <h3 class="text-gold font-bold mb-3">Repayment Schedule</h3>
        <table class="w-full text-sm">
            <thead class="border-b border-gold/30 text-gold-400 text-xs">
                <tr>
                    <th class="text-left p-2">#</th>
                    <th class="text-left p-2">Due</th>
                    <th class="text-right p-2">Amount</th>
                    <th class="text-left p-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($loan->repayments as $i => $r)
                    <tr class="border-b border-gold/10">
                        <td class="p-2">{{ $i + 1 }}</td>
                        <td class="p-2 text-ivory/70">{{ optional($r->due_date)->format('M d, Y') }}</td>
                        <td class="p-2 text-right">{{ number_format($r->amount, 2) }}</td>
                        <td class="p-2 {{ $r->status === 'paid' ? 'text-green-400' : 'text-yellow-400' }}">{{ ucfirst($r->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
