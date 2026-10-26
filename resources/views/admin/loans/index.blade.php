@extends('admin.layouts.app')

@section('page-title', 'Loans')
@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-white">Loan Applications</h2>
            <p class="text-sm text-gray-400">Review, approve, disburse, or reject loan applications.</p>
        </div>
    </div>

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded">{{ $errors->first() }}</div> @endif

    <div class="admin-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 border-b border-gray-800 text-gray-400 text-xs uppercase">
                <tr>
                    <th class="text-left p-3">Reference</th>
                    <th class="text-left p-3">User</th>
                    <th class="text-left p-3 hidden md:table-cell">Product</th>
                    <th class="text-right p-3">Principal</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-right p-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition">
                        <td class="p-3 font-mono text-xs text-gray-400">{{ $loan->reference ?? 'LOAN-' . $loan->id }}</td>
                        <td class="p-3">
                            <a href="{{ route('admin.users.show', $loan->user_id) }}" class="text-white hover:text-gold">
                                {{ $loan->user->name ?? 'Unknown' }}
                            </a>
                        </td>
                        <td class="p-3 hidden md:table-cell text-gray-400">{{ $loan->product->name ?? $loan->product_name ?? '—' }}</td>
                        <td class="p-3 text-right text-gold font-mono">KES {{ number_format($loan->principal, 2) }}</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 text-xs rounded
                                {{ in_array($loan->status, ['approved', 'active', 'completed']) ? 'bg-green-500/20 text-green-400' : '' }}
                                {{ $loan->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                {{ in_array($loan->status, ['rejected', 'defaulted']) ? 'bg-red-500/20 text-red-400' : '' }}">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <a href="{{ route('admin.loans.show', $loan) }}"
                               class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <i class="fas fa-hand-holding-usd text-4xl text-gray-700 mb-3"></i>
                            <p class="text-gray-500">No loan applications yet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $loans->links() }}</div>
</div>
@endsection
