@extends('admin.layouts.app')

@section('page-title', 'Loan Details')
@section('breadcrumbs')
    <a href="{{ route('admin.loans.index') }}" class="text-gold hover:text-yellow-400">Loans</a>
    <span class="mx-1">/</span> {{ $loan->reference ?? 'LOAN-' . $loan->id }}
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded">{{ $errors->first() }}</div> @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="admin-card p-5 space-y-3">
            <h3 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Loan Details</h3>
            <p><span class="text-gray-400">Reference:</span> <span class="text-white font-mono">{{ $loan->reference ?? 'LOAN-' . $loan->id }}</span></p>
            <p><span class="text-gray-400">Product:</span> <span class="text-white">{{ $loan->product->name ?? $loan->product_name ?? '—' }}</span></p>
            <p><span class="text-gray-400">Principal:</span> <span class="text-gold font-bold">KES {{ number_format($loan->principal, 2) }}</span></p>
            <p><span class="text-gray-400">Interest:</span> <span class="text-white">{{ $loan->interest_rate ?? '—' }}%</span></p>
            <p><span class="text-gray-400">Term:</span> <span class="text-white">{{ $loan->duration_days ?? '—' }} days</span></p>
            <p><span class="text-gray-400">Total Repayable:</span> <span class="text-white">KES {{ number_format($loan->total_payable ?? $loan->balance ?? 0, 2) }}</span></p>
            <p><span class="text-gray-400">Balance:</span> <span class="text-white">KES {{ number_format($loan->balance ?? 0, 2) }}</span></p>
            <p><span class="text-gray-400">Status:</span>
                <span class="px-2 py-0.5 text-xs rounded
                    {{ in_array($loan->status, ['approved', 'active', 'completed']) ? 'bg-green-500/20 text-green-400' : '' }}
                    {{ $loan->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                    {{ in_array($loan->status, ['rejected', 'defaulted']) ? 'bg-red-500/20 text-red-400' : '' }}">
                    {{ ucfirst($loan->status) }}
                </span>
            </p>
            <p><span class="text-gray-400">Applied:</span> <span class="text-white">{{ $loan->created_at->format('M d, Y H:i') }}</span></p>
        </div>

        <div class="admin-card p-5 space-y-3">
            <h3 class="text-lg font-semibold text-gold pb-2 border-b border-gray-800">Borrower</h3>
            <p><span class="text-gray-400">Name:</span> <span class="text-white font-medium">{{ $loan->user->name ?? '—' }}</span></p>
            <p><span class="text-gray-400">Email:</span> <span class="text-white">{{ $loan->user->email ?? '—' }}</span></p>
            <p><span class="text-gray-400">Phone:</span> <span class="text-white">{{ $loan->user->phone ?? '—' }}</span></p>
            <a href="{{ route('admin.users.show', $loan->user_id) }}" class="inline-block text-xs text-gold hover:text-yellow-400 mt-2">
                <i class="fas fa-external-link-alt mr-1"></i> Full user profile
            </a>

            <div class="pt-4 border-t border-gray-800 space-y-2">
                @if($loan->status === 'pending')
                    <form method="POST" action="{{ route('admin.loans.approve', $loan) }}" onsubmit="return confirm('Approve this loan?');">
                        @csrf
                        <button class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded">
                            <i class="fas fa-check mr-1"></i> Approve Loan
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.loans.reject', $loan) }}" onsubmit="return confirm('Reject this loan?');">
                        @csrf
                        <button class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded">
                            <i class="fas fa-times mr-1"></i> Reject Loan
                        </button>
                    </form>
                @elseif($loan->status === 'approved')
                    <form method="POST" action="{{ route('admin.loans.disburse', $loan) }}" onsubmit="return confirm('Disburse KES {{ number_format($loan->principal, 2) }} to the borrower\'s wallet?');">
                        @csrf
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded">
                            <i class="fas fa-money-bill-wave mr-1"></i> Disburse Funds
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if($loan->repayments && $loan->repayments->count() > 0)
        <div class="admin-card p-5">
            <h3 class="text-lg font-semibold text-gold mb-3">Repayment Schedule</h3>
            <table class="w-full text-sm">
                <thead class="border-b border-gray-800 text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="text-left p-2">Due Date</th>
                        <th class="text-right p-2">Amount</th>
                        <th class="text-right p-2">Paid</th>
                        <th class="text-left p-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loan->repayments as $r)
                        <tr class="border-b border-gray-800/50">
                            <td class="p-2 text-xs text-gray-400">{{ $r->due_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="p-2 text-right text-gold">KES {{ number_format($r->amount ?? 0, 2) }}</td>
                            <td class="p-2 text-right text-white">KES {{ number_format($r->amount_paid ?? 0, 2) }}</td>
                            <td class="p-2">
                                <span class="px-2 py-0.5 text-xs rounded
                                    {{ $r->status === 'paid' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $r->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                    {{ $r->status === 'overdue' ? 'bg-red-500/20 text-red-400' : '' }}">
                                    {{ ucfirst($r->status ?? 'pending') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
