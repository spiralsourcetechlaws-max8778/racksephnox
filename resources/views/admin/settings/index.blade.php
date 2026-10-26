@extends('admin.layouts.app')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-white">Global Platform Settings</h2>
</div>

@if(session('success')) 
    <div class="bg-green-500/20 text-green-400 p-3 rounded mb-4">{{ session('success') }}</div> 
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    
    <!-- General Settings -->
    <div class="md:col-span-2 bg-gray-900 border border-gray-800 p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold text-gold mb-4 border-b border-gray-800 pb-2">General Configuration</h3>
        
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Site Name</label>
                    <input type="text" name="site_name" value="{{ $settings['site_name'] ?? 'Racksephnox' }}" class="w-full border border-gray-700 bg-gray-800 text-white rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Support Email</label>
                    <input type="email" name="support_email" value="{{ $settings['support_email'] ?? '' }}" class="w-full border border-gray-700 bg-gray-800 text-white rounded px-3 py-2" required>
                </div>
            </div>

            <h3 class="text-lg font-semibold text-gold mt-8 mb-4 border-b border-gray-800 pb-2">Financial Limits & Fees</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Deposit Fee (%)</label>
                    <input type="number" step="0.01" name="deposit_fee_percent" value="{{ $settings['deposit_fee_percent'] ?? '0' }}" class="w-full border border-gray-700 bg-gray-800 text-white rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Withdrawal Fee (%)</label>
                    <input type="number" step="0.01" name="withdrawal_fee_percent" value="{{ $settings['withdrawal_fee_percent'] ?? '0' }}" class="w-full border border-gray-700 bg-gray-800 text-white rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Min Withdrawal (KES)</label>
                    <input type="number" step="1" name="min_withdrawal" value="{{ $settings['min_withdrawal'] ?? '100' }}" class="w-full border border-gray-700 bg-gray-800 text-white rounded px-3 py-2" required>
                </div>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition">
                Save Settings
            </button>
        </form>
    </div>

    <!-- System Actions -->
    <div class="bg-gray-900 border border-gray-800 p-6 rounded-lg shadow h-fit">
        <h3 class="text-lg font-semibold text-gold mb-4 border-b border-gray-800 pb-2">System Actions</h3>
        
        <div class="mb-6">
            <p class="text-sm text-gray-400 mb-2">Clear all cached routes, views, and configurations. Use this after making UI changes.</p>
            <form method="POST" action="{{ route('admin.settings.cache-clear') }}">
                @csrf
                <button type="submit" class="w-full bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition">
                    🧹 Clear Application Cache
                </button>
            </form>
        </div>

        <div class="border-t border-gray-800 pt-4">
            <p class="text-sm text-gray-400 mb-2">Put the entire platform into maintenance mode. Users will see a 503 error.</p>
            <form method="POST" action="{{ route('admin.settings.maintenance') }}">
                @csrf
                @if(($settings['maintenance_mode'] ?? '0') === '1')
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition">
                        🟢 Disable Maintenance Mode
                    </button>
                @else
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition">
                        🔴 Enable Maintenance Mode
                    </button>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
