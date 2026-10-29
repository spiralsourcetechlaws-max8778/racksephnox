@extends('admin.layouts.app')
@section('page-title', 'Loans')
@section('content')
<div class="space-y-4">
    <h1 class="text-2xl font-bold text-white">Loan Applications</h1>
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 text-gray-400 text-xs uppercase"><tr><th class="p-3 text-left">User</th><th class="p-3 text-right">Principal</th><th class="p-3 text-left">Status</th><th class="p-3 text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($loans as $loan)
                <tr class="border-t border-gray-800">
                    <td class="p-3 text-white">{{ $loan->user?->name ?? 'Unknown' }}</td>
                    <td class="p-3 text-right text-gold font-mono">KES {{ number_format($loan->principal, 2) }}</td>
                    <td class="p-3"><span class="px-2 py-0.5 text-xs rounded {{ $loan->status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' }}">{{ ucfirst($loan->status) }}</span></td>
                    <td class="p-3 text-right"><a href="{{ route('admin.loans.show', $loan) }}" class="px-2 py-1 bg-blue-600 text-white text-xs rounded">Review</a></td>
                </tr>
                @empty
                <tr><td colspan="4" class="p-6 text-center text-gray-500">No loans.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $loans->links() }}
</div>
@endsection
