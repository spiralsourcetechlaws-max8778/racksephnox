@extends('admin.layouts.app')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-white">User Profile: {{ $user->name }}</h2>
    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded">Back to Users</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- User Info Card -->
    <div class="bg-gray-900 border border-gray-800 p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold text-gold mb-4 border-b border-gray-800 pb-2">Account Details</h3>
        <p class="text-gray-300 mb-2"><strong>ID:</strong> {{ $user->id }}</p>
        <p class="text-gray-300 mb-2"><strong>Email:</strong> {{ $user->email }}</p>
        <p class="text-gray-300 mb-2"><strong>Phone:</strong> {{ $user->phone ?? 'N/A' }}</p>
        <p class="text-gray-300 mb-2"><strong>Joined:</strong> {{ $user->created_at->format('M d, Y') }}</p>
        <p class="text-gray-300 mb-2"><strong>Referral Code:</strong> {{ $user->referral_code }}</p>
        
        <div class="mt-4 pt-4 border-t border-gray-800">
            <h4 class="text-md font-semibold text-gold mb-2">Wallet Balance</h4>
            <p class="text-3xl font-bold text-green-400">KES {{ number_format($user->wallet->balance ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Manual Wallet Adjustment Card -->
    <div class="bg-gray-900 border border-gray-800 p-6 rounded-lg shadow border-t-4 border-t-red-500">
        <h3 class="text-lg font-semibold text-red-400 mb-4">⚠️ Manual Wallet Adjustment</h3>
        <p class="text-sm text-gray-400 mb-4">Use this tool with caution. All adjustments are permanently logged and cannot be reversed.</p>
        
        @if(session('success'))
            <div class="bg-green-500/20 text-green-400 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-500/20 text-red-400 p-3 rounded mb-4">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.users.adjust-wallet', $user) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Adjustment Type</label>
                <select name="type" class="w-full border border-gray-700 bg-gray-800 text-white rounded px-3 py-2" required>
                    <option value="credit">Credit (Add Funds)</option>
                    <option value="debit">Debit (Remove Funds)</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Amount (KES)</label>
                <input type="number" name="amount" step="0.01" min="1" class="w-full border border-gray-700 bg-gray-800 text-white rounded px-3 py-2" required placeholder="e.g. 500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Reason for Adjustment (Required for Audit Log)</label>
                <input type="text" name="reason" class="w-full border border-gray-700 bg-gray-800 text-white rounded px-3 py-2" required placeholder="e.g. Failed M-Pesa callback fix">
            </div>
            
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition">
                Execute Adjustment
            </button>
        </form>
    </div>
</div>
@endsection
