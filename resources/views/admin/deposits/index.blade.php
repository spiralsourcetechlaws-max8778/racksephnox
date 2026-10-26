@extends('admin.layouts.app')
@section('content')
<h2 class="text-2xl font-bold text-white mb-6">Pending Deposits</h2>
@if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded mb-4">{{ session('success') }}</div> @endif
@if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded mb-4">{{ $errors->first() }}</div> @endif

<div class="bg-gray-900 rounded-lg shadow overflow-hidden border border-gray-800">
    <table class="w-full text-left text-gray-300">
        <thead class="bg-gray-800 text-gray-400 text-sm">
            <tr><th class="p-4">User</th><th class="p-4">Amount</th><th class="p-4">Method</th><th class="p-4">Date</th><th class="p-4 text-right">Actions</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            @forelse($deposits as $deposit)
            <tr>
                <td class="p-4">{{ $deposit->user->name ?? 'Unknown' }}</td>
                <td class="p-4 font-bold text-green-400">KES {{ number_format($deposit->amount, 2) }}</td>
                <td class="p-4">{{ $deposit->payment_method ?? 'M-Pesa' }}</td>
                <td class="p-4">{{ $deposit->created_at->format('M d, H:i') }}</td>
                <td class="p-4 text-right flex gap-2 justify-end">
                    <form method="POST" action="{{ route('admin.deposits.verify', $deposit) }}">@csrf <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">Verify</button></form>
                    <form method="POST" action="{{ route('admin.deposits.reject', $deposit) }}">@csrf <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">Reject</button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="p-6 text-center text-gray-500">No pending deposits.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $deposits->links() }}</div>
@endsection
